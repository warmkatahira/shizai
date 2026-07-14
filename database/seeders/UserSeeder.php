<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 動作確認用ユーザー。パスワードは管理者以外すべて password。
 * OfficeSeeder より後に実行すること。
 */
class UserSeeder extends Seeder
{
    /**
     * 各拠点の所長。[営業所コード => [氏名, メール]]
     * 第1・第2以外は実名が未確定のため仮置き（管理画面から差し替え可）。
     */
    private const MANAGERS = [
        'honsha' => ['本社 所長（仮）', 'honsha-manager@example.com'],
        '1st' => ['里本 佳隆', 'ty01@warm.co.jp'],
        '2nd' => ['曽根田 裕也', 'soneda@warm.co.jp'],
        '3rd' => ['第3営業所 所長（仮）', '3rd-manager@example.com'],
        'LS' => ['ロジステーション 所長（仮）', 'ls-manager@example.com'],
        'LP' => ['ロジポート 所長（仮）', 'lp-manager@example.com'],
        'LC' => ['ロジコンタクト 所長（仮）', 'lc-manager@example.com'],
        'IMP' => ['IMP三郷 所長（仮）', 'imp-manager@example.com'],
        'HR' => ['広島営業所 所長（仮）', 'hr-manager@example.com'],
    ];

    public function run(): void
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 't.katahira@warm.co.jp',
            'password' => Hash::make('katahira134'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 総務
        $generalAffairs = [
            ['大泉 一弘', 'ooizumi@warm.co.jp'],
            ['並木 拓', 'namiki@warm.co.jp'],
            ['堀内 正智', 'm.horiuchi@warm.co.jp'],
            ['岡野 麻由子', 'm.okano@warm.co.jp'],
        ];

        foreach ($generalAffairs as [$name, $email]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => User::ROLE_GENERAL_AFFAIRS,
                'is_manager' => false,
            ]);
        }

        // 営業所ユーザー：各拠点に「所長」と「申請用ユーザー（拠点で1人を使い回す）」を1人ずつ
        foreach (Office::orderBy('sort_order')->get() as $office) {
            [$managerName, $managerEmail] = self::MANAGERS[$office->code];

            User::create([
                'name' => $managerName,
                'email' => $managerEmail,
                'password' => Hash::make('password'),
                'role' => User::ROLE_SALES,
                'office_id' => $office->id,
                'is_manager' => true,
            ]);

            User::create([
                'name' => $office->name . '（申請用）',
                'email' => strtolower($office->code) . '@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SALES,
                'office_id' => $office->id,
                'is_manager' => false,
            ]);
        }
    }
}
