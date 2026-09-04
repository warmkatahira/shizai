<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 権限チェック用ミドルウェアのエイリアス登録
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            // アカウントごとに表示を許可したマスタだけを通す（管理者・総務は常に通る）
            'master' => \App\Http\Middleware\EnsureMasterVisible::class,
            // パスワードの変更を強制されているユーザーを変更画面に留める
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // 操作ログを一元記録する（terminable。全 web リクエストの後に走る）
        $middleware->appendToGroup('web', \App\Http\Middleware\LogActivity::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
