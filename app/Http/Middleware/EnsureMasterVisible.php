<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * そのマスタを扱えるユーザーだけを通す。
 * 例: ->middleware('master:suppliers')      … 一覧の閲覧
 *     ->middleware('master:suppliers,edit') … 登録・編集・削除・CSV
 *
 * 管理者・総務は常に通る。それ以外は、ユーザー管理の「表示するマスタ」で
 * アカウントごとにオンにしたものだけ通る。
 */
class EnsureMasterVisible
{
    public function handle(Request $request, Closure $next, string $master, ?string $mode = null): Response
    {
        $user = $request->user();

        $allowed = $user && ($mode === 'edit'
            ? $user->canEditMaster($master)
            : $user->canViewMaster($master));

        if (! $allowed) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
