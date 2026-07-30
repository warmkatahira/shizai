<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * 業者（仕入先）マスタ。
 * 担当者・連絡先・発注方法は業者ごとに決まるので、資材ではなくここに持つ。
 * 連絡先は発注書のヘッダーにも印字される。
 * 名前は2つ持つ。`name`＝短い表示名（画面・集計・明細のスナップショット）、
 * `formal_name`＝「株式会社」まで入った正式名称（発注書の宛名だけ）。
 * ※ `name` は MaterialSeeder と OrderSeeder から引くキーになっている（どちらも
 *    Supplier::where('name', ...)->firstOrFail()）。変えるときは両方直すこと。
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // [業者名（短い表示名）, 正式名称（発注書の宛名用）, 担当者, 固定電話, 携帯電話, FAX, 発注方法]
        // 携帯電話は分かっている業者だけ入れる（不明なぶんは null のまま画面から追記する）
        $suppliers = [
            ['セッツカートン', 'セッツカートン株式会社', '岡部', '048-218-0111', null, '048-218-0113', 'mail'],
            ['共立', '株式会社共立', '江崎', '047-379-5970', null, null, 'mail'],
            ['フレックス', '株式会社フレックス', '大原', '03-3875-5075', null, '048-997-0100', 'fax'],
            ['アイセカンド', '株式会社アイ・セカンド', '西坂', '048-557-2211', null, '048-557-1962', 'fax'],
            ['イクソブ', 'イクソブ株式会社', '橋本', '0296-48-1331', null, null, 'web'],
        ];

        foreach ($suppliers as [$name, $formalName, $person, $phone, $mobilePhone, $fax, $orderMethod]) {
            Supplier::create([
                'name' => $name,
                'formal_name' => $formalName,
                'contact_person' => $person,
                'phone' => $phone,
                'mobile_phone' => $mobilePhone,
                'fax' => $fax,
                'order_method' => $orderMethod,
            ]);
        }
    }
}
