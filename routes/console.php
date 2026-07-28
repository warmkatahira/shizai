<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| スケジュール
|--------------------------------------------------------------------------
| DBのバックアップを毎日 14:02（日本時間）に取得する（spatie/laravel-backup）。
| アプリのタイムゾーンは UTC のため、timezone を明示して JST に合わせる。
| 古いバックアップは保持設定（config/backup.php）に従って掃除する。
|
| ※本番サーバーで「* * * * * php artisan schedule:run」の cron 登録が必要。
*/
Schedule::command('backup:clean')
    ->dailyAt('02:45')
    ->timezone('Asia/Tokyo');

Schedule::command('backup:run --only-db')
    ->dailyAt('14:02')
    ->timezone('Asia/Tokyo');
