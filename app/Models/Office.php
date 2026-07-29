<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * 営業所モデル。
 */
#[Fillable(['name', 'code', 'postal_code', 'address', 'tel', 'fax', 'sort_order', 'is_active'])]
class Office extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * 営業所1件の入力チェック。編集フォームとCSV取り込みで共有する。
     * $ignoreId は更新する営業所のID（自分自身を unique の対象から外す）。
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('offices', 'code')->ignore($ignoreId)],
            'postal_code' => ['nullable', 'string', 'max:8'],
            'address' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    /** 入力チェックのメッセージに出す項目名 */
    public static function attributeNames(): array
    {
        return [
            'name' => '営業所名',
            'code' => '営業所コード',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'tel' => '電話番号',
            'fax' => 'FAX番号',
            'sort_order' => '表示順',
            'is_active' => '有効',
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
