<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersByPeriod;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $logs = ActivityLog::with(['user', 'office'])
            // 種別（action 先頭のカテゴリ）で絞る
            ->when($request->filled('category'),
                fn ($q) => $q->where('action', 'like', $request->input('category') . '.%'))
            // 操作者で絞る
            ->when($request->filled('user_id'),
                fn ($q) => $q->where('user_id', $request->input('user_id')))
            // 内容キーワード
            ->when($request->filled('keyword'),
                fn ($q) => $q->where('description', 'like', '%' . $request->input('keyword') . '%'))
            // 期間（操作日時）
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'categories' => ActivityLog::CATEGORIES,
            // 操作者プルダウン（ログに登場するユーザーだけ出す）
            'users' => User::whereIn('id', ActivityLog::query()->select('user_id')->distinct())
                ->orderBy('name')->get(),
            'filters' => $request->only(['category', 'user_id', 'keyword', 'date_from', 'date_to']),
        ]);
    }
}
