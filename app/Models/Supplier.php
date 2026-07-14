<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 業者（仕入先）モデル。
 */
#[Fillable(['name', 'code', 'contact_person', 'phone', 'fax', 'email', 'order_method', 'is_active'])]
class Supplier extends Model
{
    /** 発注方法（業者ごとに決まる） */
    public const ORDER_METHODS = [
        'mail' => 'メール',
        'phone' => '電話',
        'fax' => 'FAX',
        'web' => 'web',
    ];

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

    /** 発注方法のラベル（メール／電話／FAX／web） */
    public function orderMethodLabel(): ?string
    {
        return self::ORDER_METHODS[$this->order_method] ?? null;
    }
}
