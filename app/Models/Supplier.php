<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 業者（仕入先）マスタモデル。
 */
#[Fillable(['name', 'code', 'contact_person', 'phone', 'fax', 'email', 'is_active'])]
class Supplier extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** この業者を仕入先とする資材 */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
