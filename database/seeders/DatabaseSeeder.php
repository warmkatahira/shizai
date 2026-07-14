<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 動作確認用の初期データを投入する。
     * 外部キーの依存があるため、この順番で実行する。
     */
    public function run(): void
    {
        $this->call([
            OfficeSeeder::class,
            UserSeeder::class,      // 営業所が必要
            CategorySeeder::class,
            SupplierSeeder::class,
            MaterialSeeder::class,  // カテゴリと業者が必要
        ]);
    }
}
