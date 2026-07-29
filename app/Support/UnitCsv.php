<?php

namespace App\Support;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

/**
 * 単位マスタのCSV出力・取り込み。共通処理は MasterCsv を参照。
 *
 * 単位名は資材CSVから引くキーになっているので、重複できない（DBにも unique）。
 */
class UnitCsv extends MasterCsv
{
    public const HEADERS = ['ID', '単位名', '表示順', '有効'];

    public static function headers(): array
    {
        return self::HEADERS;
    }

    protected static function model(): string
    {
        return Unit::class;
    }

    protected static function label(): string
    {
        return '単位';
    }

    protected static function uniqueColumns(): array
    {
        return ['name' => '単位名'];
    }

    public static function row(Model $unit): array
    {
        return [
            $unit->id,
            $unit->name,
            $unit->sort_order,
            self::boolText((bool) $unit->is_active),
        ];
    }

    protected static function attributes(array $cols, array $context): array
    {
        return [
            'name' => $cols[1],
            'sort_order' => self::sortOrder($cols[2]),
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[3], default: true),
        ];
    }
}
