<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * そのマスタを閲覧できるユーザーだけを通す。
 * 例: ->middleware('master:suppliers')
 *
 * 管理者・総務は常に通る。それ以外は、ユーザー管理の「表示するマスタ」で
 * オンにしたものだけ（一覧の閲覧のみ。登録・編集・削除・CSVは role ミドルウェアで別に塞ぐ）。
 */
class EnsureMasterVisible
{
    public function handle(Request $request, Closure $next, string $master): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canViewMaster($master)) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
