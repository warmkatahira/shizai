<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * 業者（仕入先）モデル。
 *
 * 名前は2つ持つ。
 * - `name`        … 短い表示名。画面・集計・発注明細のスナップショットはすべてこちら
 * - `formal_name` … 「株式会社」まで入った正式名称。発注書の宛名（〜御中）だけで使う（任意）
 */
#[Fillable(['name', 'formal_name', 'contact_person', 'phone', 'mobile_phone', 'fax', 'email', 'order_method', 'is_active'])]
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

    /**
     * 業者1件の入力チェック。編集フォームとCSV取り込みで共有する。
     * $ignoreId は更新する業者のID（unique の列は無いので今は使わないが、他のマスタと形を揃える）。
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // 発注書の宛名だけに使う。空なら name をそのまま使う（Supplier::formalName）
            'formal_name' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:50'],
            // 電話は固定と携帯を別に持つ（担当者が外に出ている業者は携帯にかける）
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile_phone' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'order_method' => ['nullable', Rule::in(array_keys(self::ORDER_METHODS))],
            'is_active' => ['boolean'],
        ];
    }

    /** 入力チェックのメッセージに出す項目名 */
    public static function attributeNames(): array
    {
        return [
            'name' => '業者名',
            'formal_name' => '正式名称',
            'contact_person' => '担当者名',
            'phone' => '固定電話番号',
            'mobile_phone' => '携帯電話番号',
            'fax' => 'FAX番号',
            'email' => 'メールアドレス',
            'order_method' => '発注方法',
            'is_active' => '有効',
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
