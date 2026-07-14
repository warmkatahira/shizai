<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 資材マスタモデル。
 */
#[Fillable([
    'name', 'category_id', 'supplier_id', 'contact_person', 'contact', 'order_method',
    'length_mm', 'width_mm', 'height_mm',
    'unit', 'unit_price', 'min_lot_qty', 'min_lot_unit', 'has_imprint', 'note', 'is_active',
])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'has_imprint' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** 商品カテゴリ */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** 仕入先業者 */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** 縦×横×高（mm）。1つも入力がなければ null */
    public function sizeText(): ?string
    {
        if ($this->length_mm === null && $this->width_mm === null && $this->height_mm === null) {
            return null;
        }

        $part = fn (?int $mm) => $mm === null ? '—' : (string) $mm;

        return $part($this->length_mm) . '×' . $part($this->width_mm) . '×' . $part($this->height_mm);
    }

    /** 最低ロット（例：2,700枚）。数量が無ければ null */
    public function minLotText(): ?string
    {
        if ($this->min_lot_qty === null) {
            return null;
        }

        return number_format($this->min_lot_qty) . ($this->min_lot_unit ?? '');
    }
}
