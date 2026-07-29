<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 発注申請（ヘッダー）モデル。
 */
#[Fillable([
    'office_id', 'supplier_id', 'requested_by', 'requester_name', 'status',
    'note', 'supplier_note', 'desired_delivery_date',
    'manager_approved_by', 'manager_approved_at',
    'reviewed_by', 'reviewed_at', 'ordered_by', 'ordered_at',
    'is_special_approval', 'special_reason',
    'reject_reason', 'rejected_by',
    'return_reason', 'returned_by', 'returned_at',
    'post_order_note', 'post_order_note_updated_by', 'post_order_note_updated_at',
])]
class Order extends Model
{
    /** ステータス定数 */
    public const STATUS_PENDING_MANAGER = 'pending_manager'; // 所長承認待ち
    public const STATUS_PENDING_AFFAIRS = 'pending_affairs'; // 総務承認待ち
    public const STATUS_PENDING_ORDER = 'pending_order';     // 発注待ち（総務承認済み。発注書を出せば発注済になる）
    public const STATUS_ORDERED = 'ordered';                 // 発注済（発注書を出した）
    public const STATUS_RETURNED = 'returned';               // 差し戻し（申請者が修正して再申請する）
    public const STATUS_REJECTED = 'rejected';               // 却下（ここで終わり）

    /** ステータスのラベル */
    public const STATUS_LABELS = [
        self::STATUS_PENDING_MANAGER => '所長承認待ち',
        self::STATUS_PENDING_AFFAIRS => '総務承認待ち',
        self::STATUS_PENDING_ORDER => '発注待ち',
        self::STATUS_ORDERED => '発注済',
        self::STATUS_RETURNED => '差し戻し',
        self::STATUS_REJECTED => '却下',
    ];

    /** 納入希望日が「近い」とみなす日数（deliveryUrgency） */
    private const DELIVERY_SOON_DAYS = 3;

    protected function casts(): array
    {
        return [
            'manager_approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'ordered_at' => 'datetime',
            'returned_at' => 'datetime',
            'post_order_note_updated_at' => 'datetime',
            'desired_delivery_date' => 'date',
            'is_special_approval' => 'boolean',
        ];
    }

    /** 発注書に印字する発注NO（orders の連番） */
    public function purchaseOrderNo(): string
    {
        return '#' . $this->id;
    }

    /** 発注先の業者（1申請＝1業者） */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** 発注元の営業所 */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** 申請者 */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** 所長の承認者 */
    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    /** 総務の確認者 */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** 発注書を出した人（＝実際に業者へ発注した人） */
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    /** 却下者 */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** 差し戻した人 */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /** 発注後メモを最後に更新した人 */
    public function postOrderNoteUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'post_order_note_updated_by');
    }

    /** 明細 */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** ステータスの日本語ラベル */
    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    // ---- 承認の経緯（詳細画面のタイムライン） ----

    /** タイムラインの各段階の状態 */
    public const STEP_DONE = 'done';        // 済んだ
    public const STEP_CURRENT = 'current';  // いまここ（次にやること）
    public const STEP_PENDING = 'pending';  // まだ来ていない
    public const STEP_SKIPPED = 'skipped';  // 通らなかった（特例承認・所長本人の申請）
    public const STEP_STOPPED = 'stopped';  // 差し戻し・却下でここで止まった

    /**
     * 承認の経緯を、申請 → 所長承認 → 総務承認 → 発注書の作成 の順で返す。
     * 差し戻し・却下されている場合は最後にその段を足す。
     *
     * 表示専用（新しい情報は持たない。列に入っている日時と担当者を並べ替えているだけ）。
     *
     * @return array<int, array{label: string, name: ?string, at: ?\Illuminate\Support\Carbon, note: ?string, state: string}>
     */
    public function approvalSteps(): array
    {
        // 差し戻し・却下されたものは、そこで止まっているので「いまここ」を出さない
        $stopped = $this->isReturned() || $this->isRejected();

        $steps = [[
            'label' => '申請',
            'name' => $this->requester_name ?: $this->requester?->name,
            'at' => $this->created_at,
            'note' => null,
            'state' => self::STEP_DONE,
        ]];

        $steps[] = match (true) {
            (bool) $this->manager_approved_at => [
                'label' => '所長承認', 'name' => $this->managerApprover?->name,
                'at' => $this->manager_approved_at, 'note' => null, 'state' => self::STEP_DONE,
            ],
            (bool) $this->is_special_approval => [
                'label' => '所長承認', 'name' => null, 'at' => null,
                'note' => '特例承認のため省略', 'state' => self::STEP_SKIPPED,
            ],
            $this->isPendingManager() => [
                'label' => '所長承認', 'name' => null, 'at' => null, 'note' => null,
                'state' => $stopped ? self::STEP_PENDING : self::STEP_CURRENT,
            ],
            $stopped => [
                'label' => '所長承認', 'name' => null, 'at' => null, 'note' => null, 'state' => self::STEP_PENDING,
            ],
            // 所長本人の申請は所長承認から始まらない（pending_affairs スタート）
            default => [
                'label' => '所長承認', 'name' => null, 'at' => null,
                'note' => '所長本人の申請のため省略', 'state' => self::STEP_SKIPPED,
            ],
        };

        $steps[] = match (true) {
            (bool) $this->reviewed_at => [
                'label' => $this->is_special_approval ? '総務の特例承認' : '総務承認',
                'name' => $this->reviewer?->name, 'at' => $this->reviewed_at,
                'note' => $this->is_special_approval ? $this->special_reason : null,
                'state' => self::STEP_DONE,
            ],
            $this->isPendingAffairs() => [
                'label' => '総務承認', 'name' => null, 'at' => null, 'note' => null,
                'state' => $stopped ? self::STEP_PENDING : self::STEP_CURRENT,
            ],
            default => [
                'label' => '総務承認', 'name' => null, 'at' => null, 'note' => null, 'state' => self::STEP_PENDING,
            ],
        };

        $steps[] = match (true) {
            (bool) $this->ordered_at => [
                'label' => '発注書の作成（発注済）', 'name' => $this->orderedBy?->name,
                'at' => $this->ordered_at, 'note' => null, 'state' => self::STEP_DONE,
            ],
            $this->isPendingOrder() => [
                'label' => '発注書の作成', 'name' => null, 'at' => null, 'note' => null,
                'state' => $stopped ? self::STEP_PENDING : self::STEP_CURRENT,
            ],
            default => [
                'label' => '発注書の作成', 'name' => null, 'at' => null, 'note' => null, 'state' => self::STEP_PENDING,
            ],
        };

        if ($this->isReturned()) {
            $steps[] = [
                'label' => '差し戻し', 'name' => $this->returnedBy?->name, 'at' => $this->returned_at,
                'note' => $this->return_reason, 'state' => self::STEP_STOPPED,
            ];
        }

        // 却下は日時の列を持たないので、担当者と理由だけ出す
        if ($this->isRejected()) {
            $steps[] = [
                'label' => '却下', 'name' => $this->rejectedBy?->name, 'at' => null,
                'note' => $this->reject_reason, 'state' => self::STEP_STOPPED,
            ];
        }

        return $steps;
    }

    public function isPendingManager(): bool
    {
        return $this->status === self::STATUS_PENDING_MANAGER;
    }

    public function isPendingAffairs(): bool
    {
        return $this->status === self::STATUS_PENDING_AFFAIRS;
    }

    public function isPendingOrder(): bool
    {
        return $this->status === self::STATUS_PENDING_ORDER;
    }

    public function isOrdered(): bool
    {
        return $this->status === self::STATUS_ORDERED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    /**
     * 納入希望日の切迫度（表示専用）。'over'＝過ぎている / 'soon'＝3日以内 / null＝それ以外。
     *
     * まだ業者へ発注していないものだけが対象。発注済・却下は手を動かす必要が
     * 無いので、日付が過ぎていても色は付けない。
     */
    public function deliveryUrgency(): ?string
    {
        if (! $this->desired_delivery_date || $this->isOrdered() || $this->isRejected()) {
            return null;
        }

        $daysLeft = today()->diffInDays($this->desired_delivery_date, false);

        return match (true) {
            $daysLeft < 0 => 'over',
            $daysLeft <= self::DELIVERY_SOON_DAYS => 'soon',
            default => null,
        };
    }

    // ---- 誰が何をできるか ----
    // コントローラー（実行時のチェック）とビュー（ボタンの出し分け）で
    // 同じ判定を使うため、ここに集約する。

    /** この申請の一次承認者（＝同じ営業所の所長）か */
    public function isManagedBy(User $user): bool
    {
        return $user->isManager() && $user->office_id === $this->office_id;
    }

    /** 所長の一次承認ができるか */
    public function canBeManagerApprovedBy(User $user): bool
    {
        return $this->isPendingManager() && $this->isManagedBy($user);
    }

    /** 総務の承認ができるか */
    public function canBeAffairsApprovedBy(User $user): bool
    {
        return $this->isPendingAffairs() && $user->isGeneralAffairs();
    }

    /** 総務の特例承認（所長を飛ばす）ができるか */
    public function canBeSpecialApprovedBy(User $user): bool
    {
        return $this->isPendingManager() && $user->isGeneralAffairs();
    }

    /**
     * 却下できるか（却下＝そこで終了）。
     * 所長承認待ち：その営業所の所長 または 総務／総務承認待ち：総務
     */
    public function canBeRejectedBy(User $user): bool
    {
        return match (true) {
            $this->isPendingManager() => $this->isManagedBy($user) || $user->isGeneralAffairs(),
            $this->isPendingAffairs() => $user->isGeneralAffairs(),
            default => false, // 確定・却下・差し戻し中のものは却下できない
        };
    }

    /**
     * 差し戻せるか（差し戻し＝申請者に戻して修正・再申請させる）。
     * 却下と同じ範囲に加えて、**発注待ち**も総務なら戻せる
     * （発注書をまだ出していない＝業者に発注していないので取り消せる）。
     */
    public function canBeReturnedBy(User $user): bool
    {
        return match (true) {
            $this->isPendingManager() => $this->isManagedBy($user) || $user->isGeneralAffairs(),
            $this->isPendingAffairs(), $this->isPendingOrder() => $user->isGeneralAffairs(),
            default => false, // 発注済・却下・差し戻し中のものは戻せない
        };
    }

    /** 修正して再申請できるか（差し戻し中のものを、その営業所の営業所ユーザーが） */
    public function canBeEditedBy(User $user): bool
    {
        return $this->isReturned() && $user->isSales() && $user->office_id === $this->office_id;
    }

    /**
     * 削除できるか。差し戻し中・却下のものだけ（承認が進んでいるもの・発注済は消せない）。
     * その営業所の営業所ユーザー、または総務・管理者。
     */
    public function canBeDeletedBy(User $user): bool
    {
        if (! $this->isReturned() && ! $this->isRejected()) {
            return false;
        }

        return $user->isBackOffice()
            || ($user->isSales() && $user->office_id === $this->office_id);
    }

    /**
     * 発注後メモを更新できるか。
     * 発注済（＝実際に業者へ発注した後）で、総務・管理者なら誰でも更新できる。
     * 閲覧は全員できる（このメソッドは更新可否だけを見る）。
     */
    public function canUpdatePostOrderNote(User $user): bool
    {
        return $this->isOrdered() && $user->canIssuePurchaseOrder();
    }

    /** 合計金額（参考単価 × 数量の合計。単価不明の明細は0扱い） */
    public function totalPrice(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->subtotal());
    }
}
