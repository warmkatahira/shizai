<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Office;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 発注実績の集計。カテゴリ別・業者別・営業所別・資材別に切り替えて集計する。
 * 集計対象は「発注済」の申請のみ（承認待ち・却下は実績ではないため含めない）。
 * 期間は発注日（総務が発注確定した日 = reviewed_at）で絞る。
 */
class ReportController extends Controller
{
    /** 集計軸の定義。キー => [ラベル, 集計に使う列（明細のスナップショット）] */
    private const AXES = [
        'category' => ['カテゴリ別', 'order_items.category_name'],
        'supplier' => ['業者別', 'order_items.supplier_name'],
        'office' => ['営業所別', 'offices.name'],
        'material' => ['資材別', 'order_items.material_name'],
    ];

    /** 集計画面 */
    public function index(Request $request): View
    {
        $this->applyDefaultPeriod($request);
        $axis = $this->axis($request);

        return view('reports.index', [
            'axis' => $axis,
            'axisLabel' => self::AXES[$axis][0],
            'axes' => collect(self::AXES)->map(fn ($a) => $a[0]),
            'rows' => $this->aggregate($request, $axis),
            'totals' => $this->totals($request),
        ] + $this->filterOptions($request));
    }

    /** 集計結果をCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultPeriod($request);
        $axis = $this->axis($request);
        $axisLabel = self::AXES[$axis][0];
        $rows = $this->aggregate($request, $axis);
        $totals = $this->totals($request);

        $filename = 'report_' . $axis . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $axisLabel, $totals) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [rtrim($axisLabel, '別'), '発注件数', '明細数', '数量合計', '金額合計']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->label,
                    $row->order_count,
                    $row->item_count,
                    $row->quantity,
                    $row->amount,
                ]);
            }

            fputcsv($out, [
                '合計',
                $totals->order_count,
                $totals->item_count,
                $totals->quantity,
                $totals->amount,
            ]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 期間の初期値を「当月」にする。
     * 全期間スキャンを既定にすると、データが増えたときに重くなるため。
     *
     * 日付パラメータが1つも無いとき（＝メニューから開いた初回表示）だけ当月を入れる。
     * フォームを送信すると date_from / date_to は空でも必ず送られてくるので、
     * 日付欄を空にして「集計する」を押せば全期間を集計できる。
     */
    private function applyDefaultPeriod(Request $request): void
    {
        if ($request->has('date_from') || $request->has('date_to')) {
            return;
        }

        $request->merge([
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]);
    }

    /** リクエストの集計軸（不正な値は カテゴリ別 にフォールバック） */
    private function axis(Request $request): string
    {
        $axis = (string) $request->input('axis', 'category');

        return array_key_exists($axis, self::AXES) ? $axis : 'category';
    }

    /**
     * 明細を集計軸でグループ化して集計する。
     * マスタが削除・改名されても過去の実績が動かないよう、
     * 明細のスナップショット列（category_name / supplier_name / material_name）でグループ化する。
     */
    private function aggregate(Request $request, string $axis): Collection
    {
        $column = self::AXES[$axis][1];

        return $this->filteredItems($request)
            ->selectRaw("COALESCE({$column}, '（未設定）') as label")
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as amount')
            ->groupBy('label')
            ->orderByDesc('amount')
            ->get();
    }

    /**
     * 全体の合計。
     * 発注件数だけは行ごとの合算では出せない（1件の発注が複数カテゴリ／業者にまたがると
     * 各行で数えられ、合算すると重複するため）。母集合全体で DISTINCT して数える。
     */
    private function totals(Request $request): object
    {
        return $this->filteredItems($request)
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(order_items.unit_price * order_items.quantity), 0) as amount')
            ->first();
    }

    /**
     * 集計対象の明細を絞り込む。
     * 絞り込み: 期間（発注日）/ 営業所 / カテゴリ / 業者
     * 営業所ユーザーは自分の営業所の実績のみ見られる。
     */
    private function filteredItems(Request $request): Builder
    {
        $user = $request->user();

        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('offices', 'orders.office_id', '=', 'offices.id')
            // 実績なので発注済のみ
            ->where('orders.status', Order::STATUS_ORDERED)
            // 営業所ユーザーは自分の営業所に限定
            ->when($user->isSales(), fn ($q) => $q->where('orders.office_id', $user->office_id))
            ->when(! $user->isSales() && $request->filled('office_id'),
                fn ($q) => $q->where('orders.office_id', $request->input('office_id')))
            ->when($request->filled('category_id'),
                fn ($q) => $q->where('order_items.category_id', $request->input('category_id')))
            ->when($request->filled('supplier_id'),
                fn ($q) => $q->where('order_items.supplier_id', $request->input('supplier_id')))
            // 期間は発注日（総務が発注確定した日）で絞る
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('orders.reviewed_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('orders.reviewed_at', '<=', $request->input('date_to')));
    }

    /** 絞り込みフォーム用の選択肢 */
    private function filterOptions(Request $request): array
    {
        return [
            'offices' => $request->user()->isSales()
                ? collect()
                : Office::orderBy('sort_order')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'filters' => $request->only(['axis', 'office_id', 'category_id', 'supplier_id', 'date_from', 'date_to']),
        ];
    }
}
