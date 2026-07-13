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
        $orders = $this->filteredOrders($request)
            ->with(['office', 'requester'])
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
        $orders = $this->filteredOrders($request)
            ->with(['office', 'requester', 'items'])
            ->latest()
            ->get();

        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                '申請番号', '申請日', '営業所', '申請者', 'ステータス',
                '品名', '業者', '単位', '参考単価', '数量', '小計',
            ]);

            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    fputcsv($out, [
                        $order->id,
                        $order->created_at->format('Y/m/d H:i'),
                        $order->office->name,
                        $order->requester->name,
                        $order->statusLabel(),
                        $item->material_name,
                        $item->supplier_name ?? '',
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
            // 業者で絞り込み（その業者の明細を含む申請）
            ->when($request->filled('supplier_id'),
                fn ($q) => $q->whereHas('items',
                    fn ($iq) => $iq->where('supplier_id', $request->input('supplier_id'))))
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

    /** 新規発注申請フォーム（営業所ユーザーのみ） */
    public function create(): View
    {
        $materials = Material::with('supplier')->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        return view('orders.create', compact('materials'));
    }

    /** 発注申請を登録 */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'quantities' => ['array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'note' => '備考',
        ]);

        // 数量が1以上の資材だけを対象にする
        $selected = collect($validated['quantities'] ?? [])
            ->filter(fn ($qty) => (int) $qty > 0);

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'quantities' => '少なくとも1つの資材に数量を入力してください。',
            ]);
        }

        // 選ばれた資材を取得（有効なもののみ、業者も読み込む）
        $materials = Material::with('supplier')
            ->whereIn('id', $selected->keys())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // 申請者が所長なら所長承認を飛ばして総務へ、そうでなければ所長承認待ち
        $initialStatus = $user->isManager()
            ? Order::STATUS_PENDING_AFFAIRS
            : Order::STATUS_PENDING_MANAGER;

        // トランザクションでヘッダー＋明細を作成
        $order = DB::transaction(function () use ($user, $validated, $selected, $materials, $initialStatus) {
            $order = Order::create([
                'office_id' => $user->office_id,
                'requested_by' => $user->id,
                'status' => $initialStatus,
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($selected as $materialId => $qty) {
                $material = $materials->get($materialId);
                if (! $material) {
                    continue; // 無効化された資材はスキップ
                }

                $order->items()->create([
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'supplier_id' => $material->supplier_id,
                    'supplier_name' => $material->supplier?->name,
                    'unit' => $material->unit,
                    'unit_price' => $material->unit_price,
                    'quantity' => (int) $qty,
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

        $order->load(['office', 'requester', 'managerApprover', 'reviewer', 'rejectedBy', 'items']);

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
