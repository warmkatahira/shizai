<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** ログイン後のトップ画面（対応すべきこと・当月KPI・月別推移＋入口ボタン） */
    public function index(): View
    {
        $user = auth()->user();
        // 営業所ユーザーは自営業所だけ、総務・管理者は全営業所を対象にする
        $officeId = $user->isSales() ? $user->office_id : null;

        return view('dashboard', [
            'todos' => $this->todos($user),
            'kpis' => $this->monthlyKpis($officeId),
            'trend' => $this->amountTrend($officeId),
        ]);
    }

    /**
     * 「あなたが対応すべきこと」。役割ごとに、いま手を動かすべき件数とリンク先を返す。
     *
     * @return array<int, array{label: string, count: int, query: array, tone: string}>
     */
    private function todos(User $user): array
    {
        $todos = [];

        // 所長：自営業所の所長承認待ち
        if ($user->isManager()) {
            $todos[] = [
                'label' => '所長承認待ち',
                'count' => Order::where('office_id', $user->office_id)
                    ->where('status', Order::STATUS_PENDING_MANAGER)->count(),
                'query' => ['status' => Order::STATUS_PENDING_MANAGER],
                'tone' => 'amber',
            ];
        }

        // 総務・管理者：総務承認待ち・発注待ち（全営業所）
        if ($user->isBackOffice()) {
            $todos[] = [
                'label' => '総務承認待ち',
                'count' => Order::where('status', Order::STATUS_PENDING_AFFAIRS)->count(),
                'query' => ['status' => Order::STATUS_PENDING_AFFAIRS],
                'tone' => 'blue',
            ];
            $todos[] = [
                'label' => '発注待ち（発注書未作成）',
                'count' => Order::where('status', Order::STATUS_PENDING_ORDER)->count(),
                'query' => ['status' => Order::STATUS_PENDING_ORDER],
                'tone' => 'accent',
            ];
        }

        // 営業所ユーザー：自営業所の差し戻し中（修正して再申請が必要）
        if ($user->isSales()) {
            $todos[] = [
                'label' => '差し戻し中（要修正）',
                'count' => Order::where('office_id', $user->office_id)
                    ->where('status', Order::STATUS_RETURNED)->count(),
                'query' => ['status' => Order::STATUS_RETURNED],
                'tone' => 'orange',
            ];
        }

        return $todos;
    }

    /**
     * 当月のKPI（発注日 = ordered_at ベース。発注済のみ）。
     *
     * @return array{count: int, amount: float, applied: int}
     */
    private function monthlyKpis(?int $officeId): array
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // 当月の発注済（件数・金額）
        $ordered = Order::where('status', Order::STATUS_ORDERED)
            ->whereBetween('ordered_at', [$from, $to])
            ->when($officeId, fn ($q) => $q->where('office_id', $officeId));

        $amount = (float) (clone $ordered)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->sum(DB::raw('order_items.unit_price * order_items.quantity'));

        return [
            'count' => (clone $ordered)->count(),
            'amount' => $amount,
            // 当月に申請された件数（申請日 = created_at ベース）
            'applied' => Order::whereBetween('created_at', [$from, $to])
                ->when($officeId, fn ($q) => $q->where('office_id', $officeId))
                ->count(),
        ];
    }

    /**
     * 直近6ヶ月の発注金額推移（発注済・ordered_at 月別）。
     *
     * @return array<int, array{label: string, amount: float}>
     */
    private function amountTrend(?int $officeId): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(5);

        // 月ごとの合計金額を1クエリで取る
        $rows = Order::where('orders.status', Order::STATUS_ORDERED)
            ->where('orders.ordered_at', '>=', $start)
            ->when($officeId, fn ($q) => $q->where('orders.office_id', $officeId))
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw("DATE_FORMAT(orders.ordered_at, '%Y-%m') as ym")
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as amount')
            ->groupBy('ym')
            ->pluck('amount', 'ym');

        // 6ヶ月ぶんの枠を作り、データが無い月は0で埋める
        $trend = [];
        for ($i = 0; $i < 6; $i++) {
            $month = (clone $start)->addMonths($i);
            $key = $month->format('Y-m');
            $trend[] = [
                'label' => $month->format('n') . '月',
                'amount' => (float) ($rows[$key] ?? 0),
            ];
        }

        return $trend;
    }
}
