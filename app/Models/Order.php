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
])]
class Order extends Model
{
    /** ステータス定数 */
    public const STATUS_PENDING_MANAGER = 'pending_manager'; // 所長承認待ち
    public const STATUS_PENDING_AFFAIRS = 'pending_affairs'; // 総務承認待ち
    public const STATUS_PENDING_ORDER = 'pending_order';     // 発注待ち（総務承認済み。発注書を出せば発注済になる）
    public const STATUS_ORDERED = 'ordered';                 // 発注済（発注書を出した）
    public const STATUS_REJECTED = 'rejected';               // 却下

    /** ステータスのラベル */
    public const STATUS_LABELS = [
        self::STATUS_PENDING_MANAGER => '所長承認待ち',
        self::STATUS_PENDING_AFFAIRS => '総務承認待ち',
        self::STATUS_PENDING_ORDER => '発注待ち',
        self::STATUS_ORDERED => '発注済',
        self::STATUS_REJECTED => '却下',
    ];

    protected function casts(): array
    {
        return [
            'manager_approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'ordered_at' => 'datetime',
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

    /** 合計金額（参考単価 × 数量の合計。単価不明の明細は0扱い） */
    public function totalPrice(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->subtotal());
    }
}
