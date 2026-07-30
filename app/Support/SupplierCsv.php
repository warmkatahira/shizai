<?php

namespace App\Support;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * 業者マスタのCSV出力・取り込み。共通処理は MasterCsv を参照。
 *
 * 発注方法は保存値（mail/phone/fax/web）ではなく**ラベル**（メール／電話／FAX／web）で
 * 書き出す。Excelで見て分かるようにするため。取り込みはどちらの書き方でも受け取る。
 */
class SupplierCsv extends MasterCsv
{
    public const HEADERS = [
        'ID', '業者名', '正式名称', '担当者名', '固定電話番号', '携帯電話番号', 'FAX番号', 'メールアドレス', '発注方法', '有効',
    ];

    public static function headers(): array
    {
        return self::HEADERS;
    }

    protected static function model(): string
    {
        return Supplier::class;
    }

    protected static function label(): string
    {
        return '業者';
    }

    public static function row(Model $supplier): array
    {
        return [
            $supplier->id,
            $supplier->name,
            $supplier->formal_name,
            $supplier->contact_person,
            $supplier->phone,
            $supplier->mobile_phone,
            $supplier->fax,
            $supplier->email,
            $supplier->orderMethodLabel(),
            self::boolText((bool) $supplier->is_active),
        ];
    }

    protected static function attributes(array $cols, array $context): array
    {
        return [
            'name' => $cols[1],
            'formal_name' => self::nullableText($cols[2]),
            'contact_person' => self::nullableText($cols[3]),
            'phone' => self::nullableText($cols[4]),
            'mobile_phone' => self::nullableText($cols[5]),
            'fax' => self::nullableText($cols[6]),
            'email' => self::nullableText($cols[7]),
            'order_method' => self::parseOrderMethod($cols[8]),
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[9], default: true),
        ];
    }

    /** 「メール」「mail」どちらでも受け取る。空欄は未設定（null） */
    private static function parseOrderMethod(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        // ラベル（メール／電話／FAX／web）から保存値を引く
        $byLabel = array_flip(Supplier::ORDER_METHODS);

        if (isset($byLabel[$value])) {
            return $byLabel[$value];
        }

        // 保存値がそのまま書かれている場合（大文字小文字は問わない）
        $key = mb_strtolower($value);

        if (array_key_exists($key, Supplier::ORDER_METHODS)) {
            return $key;
        }

        $allowed = implode('／', Supplier::ORDER_METHODS);

        throw ValidationException::withMessages([
            'csv' => "発注方法「{$value}」は使えません。{$allowed} のいずれかを書いてください。",
        ]);
    }
}
