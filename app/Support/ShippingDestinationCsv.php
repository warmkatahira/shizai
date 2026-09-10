<?php

namespace App\Support;

use App\Models\ShippingDestination;
use Illuminate\Database\Eloquent\Model;

/**
 * 直送先マスタのCSV出力・取り込み。共通処理は MasterCsv を参照。
 *
 * 同じ名前の送り先（別支店など）を登録することはありうるので、
 * 名前の重複は弾かない（突合は他のマスタと同じく1列目のID）。
 */
class ShippingDestinationCsv extends MasterCsv
{
    public const HEADERS = [
        'ID', '直送先名', '郵便番号', '住所', '電話番号', 'FAX番号', '表示順', '有効',
    ];

    public static function headers(): array
    {
        return self::HEADERS;
    }

    protected static function model(): string
    {
        return ShippingDestination::class;
    }

    protected static function label(): string
    {
        return '直送先';
    }

    public static function row(Model $destination): array
    {
        return [
            $destination->id,
            $destination->name,
            $destination->postal_code,
            $destination->address,
            $destination->tel,
            $destination->fax,
            $destination->sort_order,
            self::boolText((bool) $destination->is_active),
        ];
    }

    protected static function attributes(array $cols, array $context): array
    {
        return [
            'name' => $cols[1],
            'postal_code' => self::nullableText($cols[2]),
            'address' => $cols[3],
            'tel' => self::nullableText($cols[4]),
            'fax' => self::nullableText($cols[5]),
            'sort_order' => self::sortOrder($cols[6]),
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[7], default: true),
        ];
    }
}
