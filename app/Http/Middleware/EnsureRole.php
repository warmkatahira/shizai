<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 指定した権限を持つユーザーだけを通す。
 * 例: ->middleware('role:admin') や 'role:admin,general_affairs'
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
