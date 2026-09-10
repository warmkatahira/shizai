<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 直送先マスタ。
 *
 * 発注申請の納入先は普段「申請した営業所」だが、客先や他社の倉庫など
 * 自営業所以外へ送りたいときに、ここから送り先を選ぶ。
 * 全営業所で共通（どの営業所からでも同じ一覧が出る）。
 */
#[Fillable(['name', 'postal_code', 'address', 'tel', 'fax', 'sort_order', 'is_active'])]
class ShippingDestination extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * 直送先1件の入力チェック。編集フォームとCSV取り込みで共有する。
     * $ignoreId は更新する直送先のID（unique を使う列が増えたときのため受けておく）。
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:8'],
            // 発注書の【納入先】欄に印字するので、住所だけは必須
            'address' => ['required', 'string', 'max:255'],
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
            'name' => '直送先名',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'tel' => '電話番号',
            'fax' => 'FAX番号',
            'sort_order' => '表示順',
            'is_active' => '有効',
        ];
    }

    /** マスタ一覧・CSVの並び順（表示順 → ID） */
    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 発注申請のプルダウンに出す直送先（有効なものだけ）。
     *
     * $keepId は「いま選ばれている直送先」。後から無効にされた直送先でも、
     * 差し戻しの再申請で選択が消えないよう選択肢に残す。
     */
    public static function options(?int $keepId = null): Collection
    {
        return self::query()
            ->where(fn ($q) => $q->where('is_active', true)->when($keepId, fn ($q) => $q->orWhere('id', $keepId)))
            ->sorted()
            ->get();
    }

    /** この直送先を指定している発注申請 */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** 〒付きの住所（発注書・詳細画面で使う） */
    public function addressText(): string
    {
        return ($this->postal_code ? "〒{$this->postal_code}　" : '') . $this->address;
    }
}
