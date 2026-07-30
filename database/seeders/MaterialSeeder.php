<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * 資材マスタ。「資材発注 詳細確認リスト」の項目をひととおり埋めたサンプル。
 * 担当者・連絡先・発注方法は業者マスタ側に持つ（SupplierSeeder）。
 * CategorySeeder / SupplierSeeder / UnitSeeder より後に実行すること。
 */
class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $suppliers = Supplier::pluck('id', 'name');
        $units = Unit::pluck('id', 'name');

        // [品名, カテゴリ, 発注業者, [縦,横,高], 発送時サイズ, 単位, 単価, ロット数, 名入れ, 備考]
        // 発送時サイズは実際に運送会社で測られたサイズ。分かるものだけ入れる（null 可）
        $rows = [
            ['60サイズ 段ボール箱', '段ボール箱', 'セッツカートン', [270, 185, 105], '60サイズ', '枚', 34.5, 500, false, null],
            ['80サイズ 段ボール箱', '段ボール箱', 'セッツカートン', [339, 249, 172], '80サイズ', '枚', 47.1, 500, false, null],
            ['A-小 段ボール箱', '段ボール箱', 'フレックス', [240, 180, 100], null, '枚', 18.2, 2000, false, '内寸 234×174×97'],
            ['ネコポス用ダンボール（A4）', '段ボール箱', 'フレックス', [null, null, null], null, '枚', 29.8, 2000, false, null],
            ['クッション封筒 K60-BOX', '袋・封筒', 'アイセカンド', [null, null, null], null, '枚', 16.8, 3600, false, '400枚入'],
            ['OPP袋 フタ無し B5サイズ', '袋・封筒', 'イクソブ', [null, null, null], null, '枚', 3.3, 8000, false, null],
            ['プチプチ袋 d36', '緩衝材', 'アイセカンド', [235, 150, null], null, '枚', 4.5, 3000, false, null],
            ['ボーガスペーパー 538×350', '緩衝材', 'フレックス', [538, 350, null], null, 'ケース', 1493, 10, false, '10本入'],
            ['OPPテープ 48μ 48×100m', 'テープ・フィルム', 'フレックス', [null, null, null], null, 'ケース', 79, 5, false, null],
            ['名入れ緩衝封筒（自社ロゴ入り）', '袋・封筒', '共立', [null, null, null], null, '枚', 24, 20000, true, '納期45〜60日'],
        ];

        foreach ($rows as [$name, $category, $supplier, $size, $shippingSize, $unit, $price, $lotQty, $imprint, $note]) {
            Material::create([
                'name' => $name,
                'category_id' => $categories[$category],
                'supplier_id' => $suppliers[$supplier],
                'length_mm' => $size[0],
                'width_mm' => $size[1],
                'height_mm' => $size[2],
                'shipping_size' => $shippingSize,
                'unit_id' => $units[$unit],
                'unit_price' => $price,
                'min_lot_qty' => $lotQty,
                'has_imprint' => $imprint,
                'note' => $note,
            ]);
        }
    }
}
