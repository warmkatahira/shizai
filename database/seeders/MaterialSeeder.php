<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * 資材マスタ。「資材発注 詳細確認リスト」の項目をひととおり埋めたサンプル。
 * CategorySeeder / SupplierSeeder より後に実行すること。
 */
class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $suppliers = Supplier::pluck('id', 'name');

        // [品名, カテゴリ, 発注業者, 担当者, 連絡先, 発注方法, [縦,横,高], 単位, 単価, ロット数, ロット単位, 名入れ, 備考]
        $rows = [
            ['60サイズ 段ボール箱', '段ボール箱', 'セッツカートン', '岡部', '048-218-0111', 'メール', [270, 185, 105], '枚', 34.5, 500, '枚', false, null],
            ['80サイズ 段ボール箱', '段ボール箱', 'セッツカートン', '岡部', '048-218-0111', 'メール', [339, 249, 172], '枚', 47.1, 500, '枚', false, null],
            ['A-小 段ボール箱', '段ボール箱', 'フレックス', '大原', '03-3875-5075', 'FAX', [240, 180, 100], '枚', 18.2, 2000, '枚', false, '内寸 234×174×97'],
            ['ネコポス用ダンボール（A4）', '段ボール箱', 'フレックス', '大原', '03-3875-5075', 'FAX', [null, null, null], '枚', 29.8, 2000, '枚', false, null],
            ['クッション封筒 K60-BOX', '袋・封筒', 'アイセカンド', '西坂', '090-7987-7184', 'FAX', [null, null, null], '枚', 16.8, 3600, '枚', false, '400枚入'],
            ['OPP袋 フタ無し B5サイズ', '袋・封筒', 'イクソブ', null, null, 'サイボウズ', [null, null, null], '枚', 3.3, 8000, '枚', false, null],
            ['プチプチ袋 d36', '緩衝材', 'アイセカンド', '西坂', '090-7987-7184', 'FAX', [235, 150, null], '枚', 4.5, 3000, '枚', false, null],
            ['ボーガスペーパー 538×350', '緩衝材', 'フレックス', '大原', '03-3875-5075', 'FAX', [538, 350, null], 'ケース', 1493, 10, 'ケース', false, '10本入'],
            ['OPPテープ 48μ 48×100m', 'テープ・フィルム', 'フレックス', '大原', '03-3875-5075', 'FAX', [null, null, null], 'ケース', 79, 5, 'ケース', false, null],
            ['名入れ緩衝封筒（自社ロゴ入り）', '袋・封筒', '共立', '江崎', '047-379-5970', 'メール', [null, null, null], '枚', 24, 20000, '枚', true, '納期45〜60日'],
        ];

        foreach ($rows as [$name, $category, $supplier, $person, $contact, $method, $size, $unit, $price, $lotQty, $lotUnit, $imprint, $note]) {
            Material::create([
                'name' => $name,
                'category_id' => $categories[$category],
                'supplier_id' => $suppliers[$supplier],
                'contact_person' => $person,
                'contact' => $contact,
                'order_method' => $method,
                'length_mm' => $size[0],
                'width_mm' => $size[1],
                'height_mm' => $size[2],
                'unit' => $unit,
                'unit_price' => $price,
                'min_lot_qty' => $lotQty,
                'min_lot_unit' => $lotUnit,
                'has_imprint' => $imprint,
                'note' => $note,
            ]);
        }
    }
}
