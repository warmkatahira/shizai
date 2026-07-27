<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ActivityLogSetting;
use App\Models\User;
use App\Support\ActivityLogCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 操作ログの閲覧＋記録オン/オフ設定（管理者のみ。ルート側で role:admin）。
 *
 * 「誰が・いつ・何をしたか」の記録を、期間・種別・操作者・キーワードで絞って一覧表示する。
 * 記録そのものは LogActivity ミドルウェア＋認証イベントが自動で行う。ここは閲覧と設定のみ。
 */
class ActivityLogController extends Controller
{
    /** 1ページの件数（発注一覧と揃える） */
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        // 初回表示は当日だけに絞る（操作ログは件数が多いので当月では範囲が広すぎる）。
        // 日付を空にして送れば全期間も見られる。
        $this->applyDefaultDay($request);

        $logs = $this->filtered($request)
            ->with(['user', 'office'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'categories' => ActivityLog::CATEGORIES,
            // 操作者プルダウンの選択肢（有効な全ユーザー。まだログが無い人でも選べる）
            'userGroups' => $this->userGroups(),
            'filters' => $request->only(['category', 'user_id', 'keyword', 'date_from', 'date_to']),
        ]);
    }

    /**
     * 操作者プルダウン用に、有効なユーザーを権限ごとにグループ分けする。
     * 営業所は所長とそれ以外で分ける（管理者 → 総務 → 営業所(所長) → 営業所 の順・各グループ氏名順）。
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, users: \Illuminate\Support\Collection}>
     */
    private function userGroups(): \Illuminate\Support\Collection
    {
        $active = User::where('is_active', true)->orderBy('name')->get();

        return collect([
            ['label' => '管理者', 'users' => $active->where('role', User::ROLE_ADMIN)->values()],
            ['label' => '総務', 'users' => $active->where('role', User::ROLE_GENERAL_AFFAIRS)->values()],
            ['label' => '営業所（所長）', 'users' => $active->where('role', User::ROLE_SALES)->where('is_manager', true)->values()],
            ['label' => '営業所', 'users' => $active->where('role', User::ROLE_SALES)->where('is_manager', false)->values()],
        ])->filter(fn ($group) => $group['users']->isNotEmpty())->values();
    }

    /**
     * 操作ログをCSVでダウンロード（Excel対応のBOM付きUTF-8）。
     * 一覧と同じ絞り込みを適用し、ページ分割の影響を受けず全件出力する。
     */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultDay($request);

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
     * 記録オン/オフの設定画面。カタログの全操作を、カテゴリごとに現在のオン/オフ付きで並べる。
     */
    public function settings(): View
    {
        // カテゴリ順に、そのカテゴリの操作定義を集める（表示順を安定させる）
        $groups = collect(ActivityLogCatalog::categories())
            ->map(fn ($label, $category) => [
                'label' => $label,
                'actions' => ActivityLogCatalog::all()
                    ->filter(fn ($def) => $def['category'] === $category)
                    ->map(fn ($def) => $def + ['enabled' => ActivityLogSetting::isEnabled($def['action'])])
                    ->values(),
            ])
            ->filter(fn ($group) => $group['actions']->isNotEmpty())
            ->values();

        return view('admin.logs.settings', ['groups' => $groups]);
    }

    /**
     * 設定の保存。チェックの入った操作だけオン、それ以外はオフにする
     * （チェックボックスは未チェックだと送られてこないので、カタログ全体を基準に判定する）。
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $checked = array_keys($request->input('actions', []));

        $states = ActivityLogCatalog::all()->keys()
            ->mapWithKeys(fn ($action) => [$action => in_array($action, $checked, true)])
            ->all();

        ActivityLogSetting::putMany($states);

        return redirect()->route('admin.logs.settings')->with('status', '操作ログの記録設定を保存しました。');
    }

    /**
     * メニューから開いた初回表示のときだけ、期間に当日を入れる。
     * フォームを送信すると date_from / date_to は空でも送られてくるので、
     * has() で初回表示かどうかを判定できる（空で送れば全期間）。
     */
    private function applyDefaultDay(Request $request): void
    {
        if ($request->has('date_from') || $request->has('date_to')) {
            return;
        }

        $today = now()->toDateString();
        $request->merge(['date_from' => $today, 'date_to' => $today]);
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
