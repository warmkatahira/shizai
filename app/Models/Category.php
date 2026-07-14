<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 商品カテゴリマスタモデル。
 */
#[Fillable(['name', 'sort_order', 'is_active'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** このカテゴリに属する資材 */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
