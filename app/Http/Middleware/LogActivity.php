<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Support\ActivityLogCatalog;
use App\Support\ActivityLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * 操作ログを一元的に記録するミドルウェア。
 *
 * 各コントローラーにログ用コードを書く代わりに、ここで
 * 「カタログ(config/activity_log.php)に載っているルート」だけを記録する。
 * 記録するかどうか（オン/オフ）は ActivityLogger 側が設定を見て最終判定する。
 *
 * ログイン系（成功/失敗/ログアウト）はルートでは成功判定が難しいため、
 * ここではなく認証イベントのリスナー（AppServiceProvider）で記録する。
 *
 * ※ terminate ではなく handle 内（$next の後）で記録する。成功判定に
 *   「このリクエストでフラッシュされた status」を見るため、セッションが
 *   保存・エイジングされる前のこのタイミングでないと正確に判定できない。
 */
class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->record($request, $response);

        return $response;
    }

    private function record(Request $request, Response $response): void
    {
        $route = $request->route();
        $name = $route?->getName();
        if (! $name) {
            return;
        }

        $entry = ActivityLogCatalog::routes()[$name] ?? null;
        if (! $entry) {
            return;
        }

        if (! $this->succeeded($request, $response)) {
            return;
        }

        $subject = $this->subjectFrom($route);

        ActivityLogger::log(
            $entry['action'],
            $this->describe($entry, $request, $subject),
            $subject,
            $subject?->office_id,
        );
    }

    /**
     * 操作が成功したか。
     * 変更操作は成功時に必ず status フラッシュを出す（バリデーション失敗時は出ない）。
     * ただし「前回リクエストの status の残り」で誤判定しないよう、
     * 今回のリクエストでフラッシュされたか（_flash.new に status が入っているか）で見る。
     * ダウンロード・PDF などリダイレクトしない応答は 2xx を成功とみなす。
     */
    private function succeeded(Request $request, Response $response): bool
    {
        if ($response->isRedirection()) {
            if (! $request->hasSession()) {
                return false;
            }

            $freshlyFlashed = (array) $request->session()->get('_flash.new', []);

            return in_array('status', $freshlyFlashed, true);
        }

        return $response->isSuccessful();
    }

    /** ルートにバインドされたモデル（{order} や {material} 等）を操作対象とする */
    private function subjectFrom(Route $route): ?Model
    {
        foreach ($route->parameters() as $param) {
            if ($param instanceof Model) {
                return $param;
            }
        }

        return null;
    }

    /** 一覧に出す説明文。ラベル＋対象（発注はID・マスタは名前）＋理由（あれば）。 */
    private function describe(array $entry, Request $request, ?Model $subject): string
    {
        $desc = $entry['label'];

        if ($subject instanceof Order) {
            $desc .= " #{$subject->getKey()}";
        } elseif ($subject && filled($subject->name ?? null)) {
            $desc .= "：{$subject->name}";
        }

        // 却下・差し戻し・特例承認は理由を添える（入力から取得）
        foreach (['reject_reason', 'return_reason', 'special_reason'] as $key) {
            if ($request->filled($key)) {
                $desc .= "（理由：{$request->input($key)}）";
                break;
            }
        }

        return $desc;
    }
}
