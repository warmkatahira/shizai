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

    protected function casts(): array
    {
        return [
            'manager_approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'ordered_at' => 'datetime',
            'returned_at' => 'datetime',
            'desired_delivery_date' => 'date',
            'is_special_approval' => 'boolean',
        ];
    }

    /** 発注書に印字する発注NO（orders の連番） */
    public function purchaseOrderNo(): string
    {
        return (string) $this->id;
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

    /** 合計金額（参考単価 × 数量の合計。単価不明の明細は0扱い） */
    public function totalPrice(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->subtotal());
    }
}
