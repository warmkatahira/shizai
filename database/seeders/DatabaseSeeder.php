<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 動作確認用の初期データを投入する。
     */
    public function run(): void
    {
        // 営業所を作成
        $tokyo = Office::create(['name' => '東京営業所', 'code' => 'TKY']);
        $osaka = Office::create(['name' => '大阪営業所', 'code' => 'OSK']);

        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 総務
        User::create([
            'name' => '総務 太郎',
            'email' => 'soumu@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_GENERAL_AFFAIRS,
        ]);

        // 営業所ユーザー（花子は東京の所長）
        User::create([
            'name' => '東京 花子（所長）',
            'email' => 'tokyo@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SALES,
            'office_id' => $tokyo->id,
            'is_manager' => true,
        ]);

        // 東京の一般ユーザー（所長でない）
        User::create([
            'name' => '東京 一般太郎',
            'email' => 'tokyo2@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SALES,
            'office_id' => $tokyo->id,
            'is_manager' => false,
        ]);

        // 大阪の所長
        User::create([
            'name' => '大阪 次郎（所長）',
            'email' => 'osaka@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SALES,
            'office_id' => $osaka->id,
            'is_manager' => true,
        ]);
    }
}
