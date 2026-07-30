<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

/**
 * 資材マスタのCSV出力・取り込み。共通処理は MasterCsv を参照。
 *
 * カテゴリ・業者・単位は**名前**で書く。マスタに無い名前はエラーにする
 * （自動で作ると、誤字がそのままマスタに入ってしまうため）。
 */
class MaterialCsv extends MasterCsv
{
    /** CSVの列。この順で出力し、この順で読む */
    public const HEADERS = [
        'ID', '品名', 'カテゴリ', '発注業者',
        '縦(mm)', '横(mm)', '高さ(mm)', '3辺計(mm)', '発送時サイズ', 'サイズ',
        '単位', '単価', '最低ロット数量',
        '名入れ', '備考', '有効',
    ];

    public static function headers(): array
    {
        return self::HEADERS;
    }

    protected static function model(): string
    {
        return Material::class;
    }

    protected static function label(): string
    {
        return '資材';
    }

    /** 資材1件をCSVの1行にする */
    public static function row(Model $material): array
    {
        return [
            $material->id,
            $material->name,
            $material->category?->name ?? '',
            $material->supplier?->name ?? '',
            $material->length_mm,
            $material->width_mm,
            $material->height_mm,
            // 3辺計は列に持たない計算値。出力するだけで、取り込みでは読み飛ばす
            $material->girthMm(),
            $material->shipping_size,
            $material->size_text,
            $material->unit?->name ?? '',
            // 単価は decimal。「34.50」ではなく「34.5」で出す（Excelで見やすいように）
            $material->unit_price === null ? '' : (float) $material->unit_price,
            $material->min_lot_qty,
            self::boolText((bool) $material->has_imprint),
            $material->note,
            self::boolText((bool) $material->is_active),
        ];
    }

    /** 名前 → ID の対応表 */
    protected static function context(): array
    {
        return [
            'categories' => Category::pluck('id', 'name'),
            'suppliers' => Supplier::pluck('id', 'name'),
            'units' => Unit::pluck('id', 'name'),
        ];
    }

    protected static function attributes(array $cols, array $context): array
    {
        return [
            'name' => $cols[1],
            'category_id' => self::lookup($context['categories'], $cols[2], 'カテゴリ'),
            'supplier_id' => self::lookup($context['suppliers'], $cols[3], '発注業者'),
            'length_mm' => self::nullableNumber($cols[4]),
            'width_mm' => self::nullableNumber($cols[5]),
            'height_mm' => self::nullableNumber($cols[6]),
            // $cols[7] は3辺計。縦横高から求まる計算値なので取り込まない（直しても無視される）
            'shipping_size' => self::nullableText($cols[8]),
            'size_text' => self::nullableText($cols[9]),
            'unit_id' => self::lookup($context['units'], $cols[10], '単位'),
            'unit_price' => self::nullableNumber($cols[11]),
            'min_lot_qty' => self::nullableNumber($cols[12]),
            'has_imprint' => self::parseBool($cols[13], default: false),
            'note' => self::nullableText($cols[14]),
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[15], default: true),
        ];
    }
}
