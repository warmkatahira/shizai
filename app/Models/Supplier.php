<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 業者（仕入先）モデル。
 *
 * 名前は2つ持つ。
 * - `name`        … 短い表示名。画面・集計・発注明細のスナップショットはすべてこちら
 * - `formal_name` … 「株式会社」まで入った正式名称。発注書の宛名（〜御中）だけで使う（任意）
 */
#[Fillable(['name', 'formal_name', 'contact_person', 'phone', 'fax', 'email', 'order_method', 'is_active'])]
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

    /**
     * 発注書の宛名に使う名前。正式名称が入っていればそれを、無ければ表示名を返す。
     * 「御中」を付けるのは呼び出し側（発注書PDF）。
     */
    public function formalName(): string
    {
        return $this->formal_name ?: $this->name;
    }

    /** 発注方法のラベル（メール／電話／FAX／web） */
    public function orderMethodLabel(): ?string
    {
        return self::ORDER_METHODS[$this->order_method] ?? null;
    }
}
