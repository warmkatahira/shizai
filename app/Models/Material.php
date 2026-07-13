<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 資材マスタモデル。
 */
#[Fillable(['name', 'category', 'supplier_id', 'unit', 'unit_price', 'is_active'])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** 仕入先業者 */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
