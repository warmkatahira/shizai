<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 営業所モデル。
 */
#[Fillable(['name', 'code', 'is_active'])]
class Office extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** この営業所に所属するユーザー */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** この営業所の所長（有効なユーザーのみ） */
    public function managers(): HasMany
    {
        return $this->hasMany(User::class)
            ->where('is_manager', true)
            ->where('is_active', true);
    }
}
