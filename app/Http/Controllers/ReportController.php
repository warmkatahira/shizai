<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Office;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Http\Controllers\Concerns\FiltersByPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 発注実績の集計。カテゴリ別・業者別・営業所別・資材別に切り替えて集計する。
 * 集計対象は「発注済」の申請のみ（承認待ち・発注待ち・却下は実績ではないため含めない）。
 * 期間は発注日（発注書を出した日 = ordered_at）で絞る。
 */
class ReportController extends Controller
{
    use FiltersByPeriod;

    /** 集計軸の定義。キー => [ラベル, 集計に使う列（明細のスナップショット）] */
    private const AXES = [
        'category' => ['カテゴリ別', 'order_items.category_name'],
        'supplier' => ['業者別', 'order_items.supplier_name'],
        'office' => ['営業所別', 'offices.name'],
        'material' => ['資材別', 'order_items.material_name'],
    ];

    /**
     * 2軸（クロス集計）。縦＝営業所 / 横＝業者で、どの営業所がどの業者にいくら発注したかを見る。
     * 1列でグループ化する単軸とは表の形が違うので、AXES とは別に扱う。
     */
    private const CROSS_AXIS = 'office_supplier';

    private const CROSS_LABEL = '営業所×業者';

    /** 集計画面 */
    public function index(Request $request): View
    {
        $this->applyDefaultPeriod($request);
        $axis = $this->axis($request);
        $isCross = $axis === self::CROSS_AXIS;

        return view('reports.index', [
            'axis' => $axis,
            'axisLabel' => $isCross ? self::CROSS_LABEL : self::AXES[$axis][0],
            'axes' => $this->axisOptions(),
            'isCross' => $isCross,
            // 単軸なら1列の集計、クロスなら営業所×業者のマトリクス
            'rows' => $isCross ? collect() : $this->aggregate($request, $axis),
            'matrix' => $isCross ? $this->crossMatrix($request) : null,
            'totals' => $this->totals($request),
        ] + $this->filterOptions($request));
    }

    /**
     * 集計結果をCSVでダウンロード（Excel対応のBOM付きUTF-8）。
     *
     * 合計行は出さない。Excel側で並べ替え・フィルタ・ピボットにかけたときに
     * 合計行まで1件のデータとして混ざってしまうため（合計は画面で見られる）。
     */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultPeriod($request);
        $axis = $this->axis($request);

        if ($axis === self::CROSS_AXIS) {
            return $this->exportCross($request);
        }

        $axisLabel = self::AXES[$axis][0];
        $rows = $this->aggregate($request, $axis);

        $filename = 'report_' . $axis . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $axisLabel) {
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

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 営業所×業者のマトリクスをCSVで出す。
     *
     * 合計行は単軸のCSVと同じく出さない（Excelでの並べ替え・ピボットに1件のデータとして
     * 混ざってしまうため。合計は画面で見られる）。営業所ごとの合計列だけは並べ替えの邪魔に
     * ならないので残す。値は金額だけ。数量は資材が違えば足しても意味がないため出さない。
     */
    private function exportCross(Request $request): StreamedResponse
    {
        $matrix = $this->crossMatrix($request);
        $filename = 'report_' . self::CROSS_AXIS . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($matrix) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_merge(['営業所'], $matrix['suppliers'], ['合計']));

            foreach ($matrix['offices'] as $office) {
                // 発注が無い組み合わせは空欄ではなく0（Excelでそのまま計算できるように）
                $cells = array_map(
                    fn (string $supplier) => $matrix['amounts'][$office][$supplier] ?? 0,
                    $matrix['suppliers'],
                );

                fputcsv($out, array_merge([$office], $cells, [$matrix['rowTotals'][$office]]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 集計軸プルダウンの選択肢。単軸4つ＋クロス集計 */
    private function axisOptions(): Collection
    {
        return collect(self::AXES)
            ->map(fn ($a) => $a[0])
            ->put(self::CROSS_AXIS, self::CROSS_LABEL);
    }

    /** リクエストの集計軸（不正な値は カテゴリ別 にフォールバック） */
    private function axis(Request $request): string
    {
        $axis = (string) $request->input('axis', 'category');

        return $this->axisOptions()->has($axis) ? $axis : 'category';
    }

    /**
     * 営業所（縦）×業者（横）のマトリクスを組み立てる。
     *
     * SQLは営業所×業者で1回 GROUP BY するだけ（返る行数は 営業所数×業者数 程度）。
     * 行・列・合計はPHP側で並べ替える。
     *
     * 業者は明細のスナップショット（`order_items.supplier_name`）でまとめる。
     * 単軸の業者別と同じ扱いで、業者マスタを改名・削除しても過去の実績は動かない。
     *
     * @return array{offices:list<string>, suppliers:list<string>, amounts:array<string,array<string,float>>,
     *               counts:array<string,array<string,int>>, rowTotals:array<string,float>,
     *               colTotals:array<string,float>, total:float}
     */
    private function crossMatrix(Request $request): array
    {
        $rows = $this->filteredItems($request)
            ->selectRaw('offices.name as office')
            // 行の並びは営業所の sort_order（グループ化した列ではないので集約して取る）
            ->selectRaw('MIN(offices.sort_order) as office_sort')
            ->selectRaw("COALESCE(order_items.supplier_name, '（未設定）') as supplier")
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as amount')
            ->groupBy('office', 'supplier')
            ->get();

        $amounts = [];
        $counts = [];
        $rowTotals = [];
        $colTotals = [];
        $officeSort = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->amount;

            $amounts[$row->office][$row->supplier] = $amount;
            // 発注件数はセルの中だけの数（1申請＝1業者なので、この数は営業所×業者で重複しない）。
            // ただし行・列の合計は金額だけにしてある（件数は母集合で DISTINCT した $totals を見る）
            $counts[$row->office][$row->supplier] = (int) $row->order_count;
            $rowTotals[$row->office] = ($rowTotals[$row->office] ?? 0) + $amount;
            $colTotals[$row->supplier] = ($colTotals[$row->supplier] ?? 0) + $amount;
            $officeSort[$row->office] = (int) $row->office_sort;
            $total += $amount;
        }

        return [
            // 営業所の並び順はどこでも sort_order
            'offices' => collect($officeSort)->sort()->keys()->all(),
            // 業者はマスタに並び順が無いので、金額の大きい順（よく使う業者が左に来る）
            'suppliers' => collect($colTotals)->sortDesc()->keys()->all(),
            'amounts' => $amounts,
            'counts' => $counts,
            'rowTotals' => $rowTotals,
            'colTotals' => $colTotals,
            'total' => $total,
        ];
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
            // 期間は発注日（発注書を出した日）で絞る
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('orders.ordered_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('orders.ordered_at', '<=', $request->input('date_to')));
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
