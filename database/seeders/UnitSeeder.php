<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * 単位マスタ。資材でよく使う単位を投入する。MaterialSeeder より前に実行すること。
 */
class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['枚', 'ケース', '本', '個', '箱', '巻', 'セット', 'ロール', 'm', 'kg'];

        foreach ($names as $i => $name) {
            Unit::create([
                'name' => $name,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }
}
