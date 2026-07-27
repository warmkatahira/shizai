<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * 操作ログのカタログ（config/activity_log.php）を読むための入口。
 * ミドルウェア・認証リスナー・設定画面が、記録定義をここ経由で参照する。
 */
class ActivityLogCatalog
{
    /** ルート名 → 記録定義 */
    public static function routes(): array
    {
        return config('activity_log.routes', []);
    }

    /** 認証イベントのキー（login/logout/failed） → 記録定義 */
    public static function auth(): array
    {
        return config('activity_log.auth', []);
    }

    /** カテゴリの表示名 */
    public static function categories(): array
    {
        return config('activity_log.categories', []);
    }

    /**
     * 記録しうる全操作の定義。action をキーにまとめる（ルート＋認証）。
     *
     * @return Collection<string, array{action: string, label: string, category: string, default: bool}>
     */
    public static function all(): Collection
    {
        return collect(self::routes())->values()
            ->merge(collect(self::auth())->values())
            ->keyBy('action');
    }

    /** その action の記録オン/オフの初期値（設定が無いときに使う） */
    public static function defaultFor(string $action): bool
    {
        return (bool) (self::all()[$action]['default'] ?? true);
    }
}
