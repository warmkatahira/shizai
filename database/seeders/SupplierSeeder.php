<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * 業者（仕入先）マスタ。
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['セッツカートン', '共立', 'フレックス', 'アイセカンド', 'イクソブ'] as $name) {
            Supplier::create(['name' => $name]);
        }
    }
}
