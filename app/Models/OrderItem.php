<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 発注明細モデル。
 */
#[Fillable(['order_id', 'material_id', 'material_name', 'supplier_id', 'supplier_name', 'unit', 'unit_price', 'quantity'])]
class OrderItem extends Model
{
    /** 所属する発注申請 */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** 元の資材（マスタ） */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /** 申請時の業者（参照用） */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** この明細の小計（参考） */
    public function subtotal(): int
    {
        return (int) $this->unit_price * $this->quantity;
    }
}
