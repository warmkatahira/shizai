<?php

namespace App\Providers;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ログイン系の操作ログ。ルートでは成功/失敗を判別しづらいので認証イベントで拾う。
        // 記録するかどうか（オン/オフ）は ActivityLogger が設定を見て判定する。
        Event::listen(Login::class, fn () => ActivityLogger::log('auth.login', 'ログインしました'));
        Event::listen(Logout::class, fn () => ActivityLogger::log('auth.logout', 'ログアウトしました'));
        Event::listen(Failed::class, fn (Failed $e) => ActivityLogger::log(
            'auth.login_failed',
            'ログインに失敗しました（ID：' . ($e->credentials['login_id'] ?? '') . '）',
        ));
    }
}
