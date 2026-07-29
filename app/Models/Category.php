<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

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

    /**
     * カテゴリ1件の入力チェック。編集フォームとCSV取り込みで共有する。
     * $ignoreId は更新するカテゴリのID（自分自身を unique の対象から外す）。
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    /** 入力チェックのメッセージに出す項目名 */
    public static function attributeNames(): array
    {
        return [
            'name' => 'カテゴリ名',
            'sort_order' => '表示順',
            'is_active' => '有効',
        ];
    }

    /** このカテゴリに属する資材 */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
