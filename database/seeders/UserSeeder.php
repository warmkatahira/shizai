<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 動作確認用ユーザー。パスワードは管理者以外すべて password。
 * OfficeSeeder より後に実行すること。
 *
 * ログインは login_id で行う。メールは通知の宛先で任意。
 * 営業所の申請用アカウントは共通で使い回すため、メールを持たない。
 */
class UserSeeder extends Seeder
{
    /**
     * 各拠点の所長。[営業所コード => [氏名, ログインID, メール]]
     * 第1・第2以外は実名が未確定のため仮置き（管理画面から差し替え可）。
     */
    private const MANAGERS = [
        'honsha' => ['本社 所長（仮）', 'honsha-manager', null],
        '1st' => ['里本 佳隆', 'ty01', 'ty01@warm.co.jp'],
        '2nd' => ['曽根田 裕也', 'soneda', 'soneda@warm.co.jp'],
        '3rd' => ['第3営業所 所長（仮）', '3rd-manager', null],
        'LS' => ['ロジステーション 所長（仮）', 'ls-manager', null],
        'LP' => ['ロジポート 所長（仮）', 'lp-manager', null],
        'LC' => ['ロジコンタクト 所長（仮）', 'lc-manager', null],
        'IMP' => ['IMP三郷 所長（仮）', 'imp-manager', null],
        'HR' => ['広島営業所 所長（仮）', 'hr-manager', null],
    ];

    public function run(): void
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'login_id' => 't.katahira',
            'email' => 't.katahira@warm.co.jp',
            'password' => Hash::make('katahira134'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 総務（承認待ちの通知が届くので、全員メールあり）
        $generalAffairs = [
            ['大泉 一弘', 'ooizumi', 'ooizumi@warm.co.jp'],
            ['並木 拓', 'namiki', 'namiki@warm.co.jp'],
            ['堀内 正智', 'm.horiuchi', 'm.horiuchi@warm.co.jp'],
            ['岡野 麻由子', 'm.okano', 'm.okano@warm.co.jp'],
        ];

        foreach ($generalAffairs as [$name, $loginId, $email]) {
            User::create([
                'name' => $name,
                'login_id' => $loginId,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => User::ROLE_GENERAL_AFFAIRS,
                'is_manager' => false,
            ]);
        }

        // 営業所ユーザー：各拠点に「所長」と「申請用ユーザー（拠点で1人を使い回す）」を1人ずつ
        foreach (Office::orderBy('sort_order')->get() as $office) {
            [$managerName, $managerLoginId, $managerEmail] = self::MANAGERS[$office->code];

            User::create([
                'name' => $managerName,
                'login_id' => $managerLoginId,
                'email' => $managerEmail,
                'password' => Hash::make('password'),
                'role' => User::ROLE_SALES,
                'office_id' => $office->id,
                'is_manager' => true,
            ]);

            // 申請用アカウントはメールを持たない（共通で使い回すため）
            User::create([
                'name' => $office->name . '（申請用）',
                'login_id' => strtolower($office->code),
                'email' => null,
                'password' => Hash::make('password'),
                'role' => User::ROLE_SALES,
                'office_id' => $office->id,
                'is_manager' => false,
            ]);
        }
    }
}
