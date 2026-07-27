<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersByPeriod;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 操作ログの閲覧（管理者のみ。ルート側で role:admin）。
 *
 * 「誰が・いつ・何をしたか」の記録を、期間・種別・操作者・キーワードで絞って一覧表示する。
 * 記録は App\Support\ActivityLogger が各操作の直後に残している。閲覧専用（作成・編集はしない）。
 */
class ActivityLogController extends Controller
{
    use FiltersByPeriod;

    /** 1ページの件数（発注一覧と揃える） */
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        // 初回表示は当月に絞る（全期間スキャンを既定にしない）。日付を空にして送れば全期間。
        $this->applyDefaultPeriod($request);

        $logs = $this->filtered($request)
            ->with(['user', 'office'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'categories' => ActivityLog::CATEGORIES,
            // 操作者プルダウンは有効な全ユーザー（まだログが無い人でも選べる）
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['category', 'user_id', 'keyword', 'date_from', 'date_to']),
        ]);
    }

    /**
     * 操作ログをCSVでダウンロード（Excel対応のBOM付きUTF-8）。
     * 一覧と同じ絞り込みを適用し、ページ分割の影響を受けず全件出力する。
     */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultPeriod($request);

        $filename = 'activity_logs_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['日時', '操作者', '種別', '操作コード', '内容', '営業所', 'IPアドレス']);

            // 大量でもメモリを食わないよう分割して回す
            $this->filtered($request)->with(['user', 'office'])->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at?->format('Y/m/d H:i:s'),
                        $log->user_name,
                        $log->categoryLabel(),
                        $log->action,
                        $log->description,
                        $log->office?->name ?? '',
                        $log->ip_address ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 一覧・CSV出力で共通の絞り込みクエリ。
     * 種別（action 先頭のカテゴリ）／操作者／内容キーワード／期間（操作日時）。新しい順。
     */
    private function filtered(Request $request): Builder
    {
        return ActivityLog::query()
            ->when($request->filled('category'),
                fn ($q) => $q->where('action', 'like', $request->input('category') . '.%'))
            ->when($request->filled('user_id'),
                fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('keyword'),
                fn ($q) => $q->where('description', 'like', '%' . $request->input('keyword') . '%'))
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->orderByDesc('id');
    }
}
