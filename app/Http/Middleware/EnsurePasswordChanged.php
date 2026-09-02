<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * users.must_change_password が立っているあいだは、パスワードを変えるまで
 * どの画面も開けないようにする（管理者が設定した初期パスワードのまま使わせないため）。
 *
 * 通すのはパスワード変更そのものとログアウトだけ。
 * フラグは PasswordController::update が変更成功時に下ろす。
 */
class EnsurePasswordChanged
{
    /** フラグが立っていても通すルート */
    private const ALLOWED = ['password.edit', 'password.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
