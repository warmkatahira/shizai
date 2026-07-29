<?php

namespace App\Support;

use App\Models\Office;
use Illuminate\Database\Eloquent\Model;

/**
 * 営業所マスタのCSV出力・取り込み。共通処理は MasterCsv を参照。
 *
 * 営業所コードはログインIDの組み立てにも使っている値なので、
 * 重複しないよう取り込み時にも見る（DBにも unique が付いている）。
 */
class OfficeCsv extends MasterCsv
{
    public const HEADERS = [
        'ID', '営業所名', '営業所コード', '郵便番号', '住所', '電話番号', 'FAX番号', '表示順', '有効',
    ];

    public static function headers(): array
    {
        return self::HEADERS;
    }

    protected static function model(): string
    {
        return Office::class;
    }

    protected static function label(): string
    {
        return '営業所';
    }

    protected static function uniqueColumns(): array
    {
        return ['code' => '営業所コード'];
    }

    public static function row(Model $office): array
    {
        return [
            $office->id,
            $office->name,
            $office->code,
            $office->postal_code,
            $office->address,
            $office->tel,
            $office->fax,
            $office->sort_order,
            self::boolText((bool) $office->is_active),
        ];
    }

    protected static function attributes(array $cols, array $context): array
    {
        return [
            'name' => $cols[1],
            'code' => self::nullableText($cols[2]),
            'postal_code' => self::nullableText($cols[3]),
            'address' => self::nullableText($cols[4]),
            'tel' => self::nullableText($cols[5]),
            'fax' => self::nullableText($cols[6]),
            'sort_order' => self::sortOrder($cols[7]),
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[8], default: true),
        ];
    }
}
