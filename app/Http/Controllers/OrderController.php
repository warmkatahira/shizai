<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Office;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Support\OrderNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * 発注申請の一覧（検索・絞り込み対応）。
     * 営業所ユーザーは自分の営業所の申請のみ、総務・管理者は全件表示。
     */
    public function index(Request $request): View
    {
        $this->applyDefaultFilters($request);

        $orders = $this->filteredOrders($request)
            ->with(['office', 'supplier', 'requester'])
            ->withCount('items')
            ->latest()
            ->get();

        return view('orders.index', array_merge(
            compact('orders'),
            $this->filterOptions($request),
        ));
    }

    /** 検索結果をCSVでダウンロード（明細1行ずつ、Excel対応のBOM付きUTF-8） */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultFilters($request);

        $orders = $this->filteredOrders($request)
            ->with(['office', 'supplier', 'requester', 'items'])
            ->latest()
            ->get();

        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                '申請番号', '申請日', '営業所', '発注業者', '発注者', 'アカウント', 'ステータス',
                '品名', 'カテゴリ', '単位', '参考単価', '数量', '小計',
            ]);

            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    fputcsv($out, [
                        $order->id,
                        $order->created_at->format('Y/m/d H:i'),
                        $order->office->name,
                        $order->supplier?->name ?? '',
                        $order->requester_name ?? '',
                        $order->requester->name,
                        $order->statusLabel(),
                        $item->material_name,
                        $item->category_name ?? '',
                        $item->unit,
                        $item->unit_price,
                        $item->quantity,
                        $item->subtotal(),
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 検索条件の初期値。メニューから開いた初回表示のときだけ効く。
     *
     * フォームを送信すると各パラメータは空でも必ず送られてくるので、
     * `has()` で「初回表示かどうか」を判定できる。
     * つまり条件を空にして検索すれば、全期間・全ステータスも見られる。
     */
    private function applyDefaultFilters(Request $request): void
    {
        // 期間（申請日）の初期値は当月。全期間を既定にすると、データが増えたとき一覧が重くなる
        if (! $request->has('date_from') && ! $request->has('date_to')) {
            $request->merge([
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }

        // 総務は「自分が承認すべき申請」から見たいので、ステータスの初期値を総務承認待ちにする
        if (! $request->has('status') && $request->user()->isGeneralAffairs()) {
            $request->merge(['status' => Order::STATUS_PENDING_AFFAIRS]);
        }
    }

    /**
     * index と export で共通の検索クエリを組み立てる。
     * 絞り込み: ステータス / 営業所 / 業者 / キーワード(品名) / 期間
     */
    private function filteredOrders(Request $request): Builder
    {
        $user = $request->user();

        return Order::query()
            // 営業所ユーザーは自分の営業所に限定
            ->when($user->isSales(), fn ($q) => $q->where('office_id', $user->office_id))
            // 総務・管理者のみ営業所で絞り込み可能
            ->when(! $user->isSales() && $request->filled('office_id'),
                fn ($q) => $q->where('office_id', $request->input('office_id')))
            ->when($request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')))
            // 業者で絞り込み（1申請＝1業者なのでヘッダーを直接見る）
            ->when($request->filled('supplier_id'),
                fn ($q) => $q->where('supplier_id', $request->input('supplier_id')))
            // 品名キーワード
            ->when($request->filled('keyword'),
                fn ($q) => $q->whereHas('items',
                    fn ($iq) => $iq->where('material_name', 'like', '%' . $request->input('keyword') . '%')))
            // 申請日の期間
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')));
    }

    /** 絞り込みフォーム用の選択肢（営業所・業者・ステータス・現在の検索条件） */
    private function filterOptions(Request $request): array
    {
        return [
            'offices' => $request->user()->isSales()
                ? collect()
                : Office::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'statuses' => Order::STATUS_LABELS,
            'filters' => $request->only(['status', 'office_id', 'supplier_id', 'keyword', 'date_from', 'date_to']),
        ];
    }

    /**
     * 新規発注申請フォーム（営業所ユーザーのみ）。
     * 1申請＝1業者。業者を選ぶとその業者の資材だけが並ぶ。
     */
    public function create(Request $request): View
    {
        // 有効な資材を1つ以上持つ業者だけを選択肢にする
        $suppliers = Supplier::where('is_active', true)
            ->whereHas('materials', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->get();

        $supplierId = $request->input('supplier_id');
        $supplier = $supplierId ? $suppliers->firstWhere('id', (int) $supplierId) : null;

        $materials = $supplier
            ? $this->activeMaterialsOf($supplier)
            : collect();

        return view('orders.create', compact('suppliers', 'supplier', 'materials'));
    }

    /** 指定業者の有効な資材（カテゴリ順 → 品名順） */
    private function activeMaterialsOf(Supplier $supplier)
    {
        // categories を join するため is_active はテーブル名で修飾する（categories 側にも同名カラムがある）
        return Material::with('category')
            ->where('materials.is_active', true)
            ->where('materials.supplier_id', $supplier->id)
            ->leftJoin('categories', 'materials.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order')->orderBy('categories.name')->orderBy('materials.name')
            ->select('materials.*')
            ->get();
    }

    /** 発注申請を登録 */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'requester_name' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
            'supplier_note' => ['nullable', 'string', 'max:1000'],
            'desired_delivery_date' => ['nullable', 'date'],
            'quantities' => ['array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ], [], [
            'supplier_id' => '発注業者',
            'requester_name' => '発注者の氏名',
            'note' => '備考',
            'supplier_note' => '業者への連絡事項',
            'desired_delivery_date' => '納入希望日',
        ]);

        // 数量が1以上の資材だけを対象にする
        $selected = collect($validated['quantities'] ?? [])
            ->filter(fn ($qty) => (int) $qty > 0);

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'quantities' => '少なくとも1つの資材に数量を入力してください。',
            ]);
        }

        // 選ばれた資材を取得。1申請＝1業者なので、選んだ業者の資材だけに限定する
        // （フォームを細工して他業者の資材を混ぜられないように、ここでも絞る）
        $materials = Material::with(['supplier', 'category'])
            ->whereIn('id', $selected->keys())
            ->where('is_active', true)
            ->where('supplier_id', $validated['supplier_id'])
            ->get()
            ->keyBy('id');

        if ($materials->isEmpty()) {
            throw ValidationException::withMessages([
                'quantities' => '選択された資材が、指定の業者の資材ではありません。',
            ]);
        }

        // 最低ロットがある資材は、ロットの倍数でしか発注できない
        // （画面側でも弾いているが、フォームを細工されても通らないようにここでも検証する）
        $lotErrors = [];
        foreach ($selected as $materialId => $qty) {
            $material = $materials->get($materialId);
            $lot = $material?->min_lot_qty;

            if ($material && $lot && (int) $qty % $lot !== 0) {
                $lotErrors[] = sprintf(
                    '「%s」は %s%s 単位で発注してください（入力値：%s）。',
                    $material->name,
                    number_format($lot),
                    $material->min_lot_unit ?? '',
                    number_format((int) $qty),
                );
            }
        }

        if ($lotErrors !== []) {
            throw ValidationException::withMessages(['quantities' => $lotErrors]);
        }

        // 申請者が所長なら所長承認を飛ばして総務へ、そうでなければ所長承認待ち
        $initialStatus = $user->isManager()
            ? Order::STATUS_PENDING_AFFAIRS
            : Order::STATUS_PENDING_MANAGER;

        // トランザクションでヘッダー＋明細を作成
        $order = DB::transaction(function () use ($user, $validated, $selected, $materials, $initialStatus) {
            $order = Order::create([
                'office_id' => $user->office_id,
                'supplier_id' => $validated['supplier_id'],
                'requested_by' => $user->id,
                'requester_name' => $validated['requester_name'],
                'status' => $initialStatus,
                'note' => $validated['note'] ?? null,
                'supplier_note' => $validated['supplier_note'] ?? null,
                'desired_delivery_date' => $validated['desired_delivery_date'] ?? null,
            ]);

            foreach ($selected as $materialId => $qty) {
                $material = $materials->get($materialId);
                if (! $material) {
                    continue; // 無効化された資材はスキップ
                }

                $order->items()->create([
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'category_id' => $material->category_id,
                    'category_name' => $material->category?->name,
                    'supplier_id' => $material->supplier_id,
                    'supplier_name' => $material->supplier?->name,
                    'unit' => $material->unit,
                    'unit_price' => $material->unit_price,
                    'quantity' => (int) $qty,
                    // 発注書に印字する項目
                    'length_mm' => $material->length_mm,
                    'width_mm' => $material->width_mm,
                    'height_mm' => $material->height_mm,
                    'min_lot_qty' => $material->min_lot_qty,
                    'min_lot_unit' => $material->min_lot_unit,
                ]);
            }

            return $order;
        });

        // 次の承認者（所長 or 総務）へメール通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyNextApprover($order);

        $next = $order->isPendingManager() ? '所長' : '総務';

        return redirect()->route('orders.show', $order)
            ->with('status', "発注申請を送信しました。{$next}の確認をお待ちください。");
    }

    /** 発注申請の詳細 */
    public function show(Request $request, Order $order): View
    {
        $this->authorizeView($request, $order);

        $order->load(['office', 'supplier', 'requester', 'managerApprover', 'reviewer', 'rejectedBy', 'items']);

        $user = $request->user();
        $isOfficeManager = $user->isManager() && $user->office_id === $order->office_id;

        // この画面で実行できるアクション
        $actions = [
            'managerApprove' => $isOfficeManager && $order->isPendingManager(),
            'affairsApprove' => $user->isGeneralAffairs() && $order->isPendingAffairs(),
            'specialApprove' => $user->isGeneralAffairs() && $order->isPendingManager(),
            'reject' => ($order->isPendingManager() && ($isOfficeManager || $user->isGeneralAffairs()))
                || ($order->isPendingAffairs() && $user->isGeneralAffairs()),
        ];

        return view('orders.show', compact('order', 'actions'));
    }

    /** 営業所ユーザーは自分の営業所の申請しか見られない */
    private function authorizeView(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user->isSales() && $order->office_id !== $user->office_id) {
            abort(403, 'この発注申請を閲覧する権限がありません。');
        }
    }
}
