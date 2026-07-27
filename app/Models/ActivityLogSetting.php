<?php

namespace App\Models;

use App\Support\ActivityLogCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 操作ログの記録オン/オフ設定（action ごと）。
 *
 * 判定は毎回のログで走るので、設定は丸ごとキャッシュして DB を叩かない。
 * 設定を保存したら putMany() でキャッシュを更新する。
 */
#[Fillable(['action', 'enabled'])]
class ActivityLogSetting extends Model
{
    /** 設定マップ（action => enabled）のキャッシュキー */
    private const CACHE_KEY = 'activity_log_settings_map';

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * その操作を記録するか。設定行があればそれに従い、無ければカタログの既定値。
     */
    public static function isEnabled(string $action): bool
    {
        $map = self::map();

        return $map[$action] ?? ActivityLogCatalog::defaultFor($action);
    }

    /** action => enabled のマップ（キャッシュ） */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()
            ->pluck('enabled', 'action')
            ->map(fn ($v) => (bool) $v)
            ->all());
    }

    /**
     * 設定画面からの一括保存。カタログにある全 action について enabled を upsert し、
     * キャッシュを作り直す。
     *
     * @param  array<string, bool>  $states  action => enabled
     */
    public static function putMany(array $states): void
    {
        foreach ($states as $action => $enabled) {
            self::updateOrCreate(['action' => $action], ['enabled' => $enabled]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
