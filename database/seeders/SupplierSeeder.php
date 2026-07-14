<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * 業者（仕入先）マスタ。連絡先は発注書のヘッダーに印字される。
 * ※ 業者名は MaterialSeeder から引くキーになっているので、変更するときは両方直すこと。
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // [業者名, 担当者, TEL, FAX]
        $suppliers = [
            ['セッツカートン', '岡部', '048-218-0111', '048-218-0113'],
            ['共立', '江崎', '047-379-5970', null],
            ['フレックス', '大原', '03-3875-5075', null],
            ['アイセカンド', '西坂', '048-557-2211', '048-557-1962'],
            ['イクソブ', '橋本', '0296-48-1331', null],
        ];

        foreach ($suppliers as [$name, $person, $phone, $fax]) {
            Supplier::create([
                'name' => $name,
                'contact_person' => $person,
                'phone' => $phone,
                'fax' => $fax,
            ]);
        }
    }
}
