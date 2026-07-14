<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * 業者（仕入先）マスタ。
 * 担当者・連絡先・発注方法は業者ごとに決まるので、資材ではなくここに持つ。
 * 連絡先は発注書のヘッダーにも印字される。
 * ※ 業者名は MaterialSeeder から引くキーになっているので、変更するときは両方直すこと。
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // [業者名, 担当者, TEL, FAX, 発注方法]
        $suppliers = [
            ['セッツカートン', '岡部', '048-218-0111', '048-218-0113', 'mail'],
            ['共立', '江崎', '047-379-5970', null, 'mail'],
            ['フレックス', '大原', '03-3875-5075', '048-997-0100', 'fax'],
            ['アイセカンド', '西坂', '048-557-2211', '048-557-1962', 'fax'],
            ['イクソブ', '橋本', '0296-48-1331', null, 'web'],
        ];

        foreach ($suppliers as [$name, $person, $phone, $fax, $orderMethod]) {
            Supplier::create([
                'name' => $name,
                'contact_person' => $person,
                'phone' => $phone,
                'fax' => $fax,
                'order_method' => $orderMethod,
            ]);
        }
    }
}
