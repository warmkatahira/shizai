<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 商品カテゴリマスタ。
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            '段ボール箱' => 10,
            '袋・封筒' => 20,
            '緩衝材' => 30,
            'テープ・フィルム' => 40,
            'パット・仕切' => 50,
            'ラベル・荷札' => 60,
        ];

        foreach ($categories as $name => $sortOrder) {
            Category::create(['name' => $name, 'sort_order' => $sortOrder]);
        }
    }
}
