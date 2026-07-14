<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 発注明細モデル。
 */
#[Fillable([
    'order_id', 'material_id', 'material_name', 'category_id', 'category_name',
    'supplier_id', 'supplier_name', 'unit', 'unit_price', 'quantity',
    'length_mm', 'width_mm', 'height_mm', 'min_lot_qty', 'min_lot_unit',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

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

    /** 申請時のカテゴリ（参照用） */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** 申請時の業者（参照用） */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** この明細の小計（参考）。単価は小数を取りうる */
    public function subtotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    /** 発注書に印字する寸法（縦×横×高）。入力がある値だけを × でつなぐ */
    public function sizeText(): ?string
    {
        $parts = array_filter(
            [$this->length_mm, $this->width_mm, $this->height_mm],
            fn (?int $mm) => $mm !== null,
        );

        return $parts === [] ? null : implode('×', $parts);
    }

    /** 発注書に印字する最低ロット（例：2,700枚）。数量が無ければ null */
    public function minLotText(): ?string
    {
        if ($this->min_lot_qty === null) {
            return null;
        }

        return number_format($this->min_lot_qty) . ($this->min_lot_unit ?? '');
    }
}
