<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 単位マスタ（枚・ケース・本 など）。資材の単位を一元管理する。
 */
#[Fillable(['name', 'sort_order', 'is_active'])]
class Unit extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** この単位を使う資材 */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
