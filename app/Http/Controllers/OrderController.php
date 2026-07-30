<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Office;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use App\Http\Controllers\Concerns\FiltersByPeriod;
use App\Http\Controllers\Concerns\RemembersLastSearch;
use App\Support\OrderNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    use FiltersByPeriod, RemembersLastSearch;

    /** 一覧の1ページあたりの件数 */
    private const PER_PAGE = 50;

    /**
     * 列見出しで並び替えできる列と、その初回の向き。
     * 日付・申請番号は「新しいものから」見たいので desc、それ以外は asc から始める。
     * ここに無いキーが来たら既定（申請日の新しい順）に落とす。
     */
    private const SORTS = [
        'id' => 'desc',
        'created_at' => 'desc',
        'desired_delivery_date' => 'asc',
        'office' => 'asc',
        'supplier' => 'asc',
        'requester_name' => 'asc',
        'items_count' => 'desc',
        'status' => 'asc',
    ];

    private const DEFAULT_SORT = 'created_at';

    /**
     * 発注申請の一覧（検索・絞り込み対応）。
     * 営業所ユーザーは自分の営業所の申請のみ、総務・管理者は全件表示。
     */
    public function index(Request $request): View
    {
        $this->applyDefaultFilters($request);

        // 詳細から「一覧に戻る」で同じ検索結果に戻れるよう、検索条件を覚えておく
        $this->rememberSearch($request);

        // 件数が増えても重くならないようページ送りにする。
        // withQueryString() で検索条件をページリンクに引き継ぐ。
        // CSVは export 側で全件出すので、ここでの分割はダウンロードに影響しない。
        $orders = $this->applySort($this->filteredOrders($request), $request)
            ->with(['office', 'supplier', 'requester'])
            ->withCount('items')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('orders.index', array_merge(
            compact('orders'),
            $this->sortState($request),
            $this->filterOptions($request),
        ));
    }

    /** 検索結果をCSVでダウンロード（明細1行ずつ、Excel対応のBOM付きUTF-8） */
    public function export(Request $request): StreamedResponse
    {
        $this->applyDefaultFilters($request);

        // 並び順も画面と揃える（見えている順にそのままCSVへ出す）
        $orders = $this->applySort($this->filteredOrders($request), $request)
            ->with(['office', 'supplier', 'requester', 'managerApprover', 'reviewer', 'orderedBy', 'rejectedBy', 'returnedBy', 'items'])
            ->withCount('items')
            ->get();

        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            $datetime = fn (?\Carbon\CarbonInterface $at) => $at?->format('Y/m/d H:i') ?? '';

            fputcsv($out, [
                // 申請
                '申請番号', '申請日', '営業所', '発注業者', '発注者', '申請アカウント', 'ステータス',
                // 承認・発注・却下の履歴
                '所長承認者', '所長承認日時',
                '総務承認者', '総務承認日時',
                '特例承認', '特例承認の理由',
                '発注書作成者', '発注日',
                '差し戻し者', '差し戻し日時', '差し戻しの理由',
                '却下者', '却下理由',
                // 申請内容
                '納入希望日', '業者への連絡事項', '備考（社内）',
                // 明細
                '品名', 'カテゴリ', '単位', '参考単価', '数量', '小計',
            ]);

            foreach ($orders as $order) {
                // 申請1件ぶんの情報。明細の行数だけ繰り返す
                $header = [
                    $order->id,
                    $datetime($order->created_at),
                    $order->office->name,
                    $order->supplier?->name ?? '',
                    $order->requester_name ?? '',
                    $order->requester->name,
                    $order->statusLabel(),

                    $order->managerApprover?->name ?? '',
                    $datetime($order->manager_approved_at),
                    $order->reviewer?->name ?? '',
                    $datetime($order->reviewed_at),
                    $order->is_special_approval ? 'あり' : '',
                    $order->special_reason ?? '',
                    $order->orderedBy?->name ?? '',
                    $datetime($order->ordered_at),
                    $order->returnedBy?->name ?? '',
                    $datetime($order->returned_at),
                    $order->return_reason ?? '',
                    $order->rejectedBy?->name ?? '',
                    $order->reject_reason ?? '',

                    $order->desired_delivery_date?->format('Y/m/d') ?? '',
                    $order->supplier_note ?? '',
                    $order->note ?? '',
                ];

                foreach ($order->items as $item) {
                    fputcsv($out, [...$header,
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

    /** 検索条件の初期値。メニューから開いた初回表示のときだけ効く */
    private function applyDefaultFilters(Request $request): void
    {
        $this->applyDefaultPeriod($request);

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

    /**
     * いま効いている並び順。不正な値・未指定は既定（申請日の新しい順）に落とす。
     *
     * @return array{sort:string, direction:string}
     */
    private function sortState(Request $request): array
    {
        $sort = (string) $request->input('sort');
        $sort = array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT;

        // 向きの指定が無いときは、その列の初回の向きを使う
        $direction = $request->input('direction') ?? self::SORTS[$sort];

        return [
            'sort' => $sort,
            'direction' => $direction === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * 列見出しで指定された並び順を適用する。
     *
     * 営業所・業者は名前が別テーブルにあるが、join すると orders と同名の列
     * （created_at / status など）が曖昧になって既存の絞り込みが壊れるので、
     * サブクエリで並べる。
     */
    private function applySort(Builder $query, Request $request): Builder
    {
        ['sort' => $sort, 'direction' => $direction] = $this->sortState($request);

        $query = match ($sort) {
            // 営業所は名前の五十音ではなく、マスタで決めた表示順に合わせる
            'office' => $query->orderBy(
                Office::select('sort_order')->whereColumn('offices.id', 'orders.office_id'), $direction),
            'supplier' => $query->orderBy(
                Supplier::select('name')->whereColumn('suppliers.id', 'orders.supplier_id'), $direction),
            // ステータスは文字列の並びに意味がないので、承認フローの順に並べる
            'status' => $query->orderByRaw(
                'FIELD(orders.status, ' . implode(',', array_fill(0, count(Order::STATUS_LABELS), '?')) . ') ' . $direction,
                array_keys(Order::STATUS_LABELS)),
            // withCount が付けた別名。orders の列ではないので修飾しない
            'items_count' => $query->orderBy('items_count', $direction),
            default => $query->orderBy('orders.' . $sort, $direction),
        };

        // 同じ値の申請が並んだときにページ送りで前後しないよう、最後は必ず申請番号で決める
        return $sort === 'id' ? $query : $query->orderBy('orders.id', 'desc');
    }

    /** 絞り込みフォーム用の選択肢（営業所・業者・ステータス・現在の検索条件） */
    private function filterOptions(Request $request): array
    {
        return [
            'offices' => $request->user()->isSales()
                ? collect()
                : Office::orderBy('sort_order')->get(),
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
        $suppliers = $this->supplierOptions();
        $supplier = $this->selectedSupplier($suppliers, $request->input('supplier_id'));

        return view('orders.create', [
            'suppliers' => $suppliers,
            'supplier' => $supplier,
            'materials' => $supplier ? $this->activeMaterialsOf($supplier) : collect(),
            'quantities' => [], // 新規なので初期数量はなし
        ]);
    }

    /**
     * 差し戻された申請を修正して再申請するフォーム（申請した営業所の営業所ユーザーのみ）。
     * 差し戻しの理由が「業者違い」のこともあるので、業者も選び直せる。
     */
    public function edit(Request $request, Order $order): View
    {
        abort_unless($order->canBeEditedBy($request->user()), 403, 'この申請を修正する権限がありません。');

        $order->load(['items', 'returnedBy']);

        $suppliers = $this->supplierOptions();
        // 業者はプルダウンで変更できる。指定がなければ今の業者の資材を出す
        $supplier = $this->selectedSupplier($suppliers, $request->input('supplier_id', $order->supplier_id));

        // 数量の初期値：業者が変わっていなければ、いまの明細をそのまま引き継ぐ
        $quantities = $supplier?->id === $order->supplier_id
            ? $order->items->pluck('quantity', 'material_id')->all()
            : [];

        return view('orders.edit', [
            'order' => $order,
            'suppliers' => $suppliers,
            'supplier' => $supplier,
            'materials' => $supplier ? $this->activeMaterialsOf($supplier) : collect(),
            'quantities' => $quantities,
        ]);
    }

    /** 業者プルダウンの選択肢（有効な資材を1つ以上持つ業者だけ） */
    private function supplierOptions(): Collection
    {
        return Supplier::where('is_active', true)
            ->whereHas('materials', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->get();
    }

    /** 選択中の業者（選択肢に無い＝無効化された業者なら null） */
    private function selectedSupplier(Collection $suppliers, mixed $supplierId): ?Supplier
    {
        return $supplierId ? $suppliers->firstWhere('id', (int) $supplierId) : null;
    }

    /** 指定業者の有効な資材（カテゴリ順 → 品名順） */
    private function activeMaterialsOf(Supplier $supplier)
    {
        return Material::with(['category', 'unit'])
            ->active()
            ->where('materials.supplier_id', $supplier->id)
            ->sortedByCategory()
            ->get();
    }

    /** 発注申請を登録 */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $this->validateOrderInput($request);
        $items = $this->buildItemSnapshots($validated);

        // トランザクションでヘッダー＋明細を作成
        $order = DB::transaction(function () use ($user, $validated, $items) {
            $order = Order::create([
                'office_id' => $user->office_id,
                'supplier_id' => $validated['supplier_id'],
                'requested_by' => $user->id,
                'requester_name' => $validated['requester_name'],
                'status' => $this->initialStatusFor($user),
                'note' => $validated['note'] ?? null,
                'supplier_note' => $validated['supplier_note'] ?? null,
                'desired_delivery_date' => $validated['desired_delivery_date'],
            ]);

            $order->items()->createMany($items);

            return $order;
        });

        // 次の承認者（所長 or 総務）へメール通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyNextApprover($order);

        $next = $order->isPendingManager() ? '所長' : '総務';

        return redirect()->route('orders.show', $order)
            ->with('status', "発注申請を送信しました。{$next}の確認をお待ちください。");
    }

    /**
     * 差し戻された申請を修正して再申請する。
     *
     * 承認は**最初からやり直す**（内容が変わっているので、所長承認済みでも所長がもう一度見る）。
     * そのため承認履歴（所長承認・総務承認・特例承認）はクリアする。
     * 差し戻しの理由・差し戻した人は「なぜ直したか」の記録として残す。
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeEditedBy($user), 403, 'この申請を修正する権限がありません。');

        $validated = $this->validateOrderInput($request);
        $items = $this->buildItemSnapshots($validated);

        DB::transaction(function () use ($order, $user, $validated, $items) {
            $order->update([
                'supplier_id' => $validated['supplier_id'],
                // 再申請したアカウントを申請者にする（申請の内容はこの人が出したものになる）
                'requested_by' => $user->id,
                'requester_name' => $validated['requester_name'],
                'status' => $this->initialStatusFor($user),
                'note' => $validated['note'] ?? null,
                'supplier_note' => $validated['supplier_note'] ?? null,
                'desired_delivery_date' => $validated['desired_delivery_date'],
                // 承認をやり直すので履歴を消す
                'manager_approved_by' => null,
                'manager_approved_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'is_special_approval' => false,
                'special_reason' => null,
            ]);

            // 明細は作り直す（資材マスタの現在値でスナップショットし直す）
            $order->items()->delete();
            $order->items()->createMany($items);
        });

        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyNextApprover($order);

        $next = $order->isPendingManager() ? '所長' : '総務';

        return redirect()->route('orders.show', $order)
            ->with('status', "再申請しました。{$next}の確認をお待ちください。");
    }

    /**
     * 発注申請を削除（差し戻し中・却下のものだけ）。
     * 明細は order_items の外部キー（cascadeOnDelete）で一緒に消える。
     */
    public function destroy(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->canBeDeletedBy($request->user()), 403, 'この申請を削除する権限がありません。');

        $id = $order->id;
        $order->delete();

        return redirect($this->backUrl($request))
            ->with('status', "発注申請 #{$id} を削除しました。");
    }

    /** 申請者が所長なら所長承認を飛ばして総務へ、そうでなければ所長承認待ち */
    private function initialStatusFor(User $user): string
    {
        return $user->isManager()
            ? Order::STATUS_PENDING_AFFAIRS
            : Order::STATUS_PENDING_MANAGER;
    }

    /** 申請フォームの入力チェック（新規申請・再申請で共通） */
    private function validateOrderInput(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'requester_name' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
            'supplier_note' => ['nullable', 'string', 'max:1000'],
            // 納入希望日は必須（総務が発注の優先順位を判断できないため）。
            // 明日以降しか選べない。当日納品は業者の締めに間に合わないため
            // （画面の date 入力にも min を入れているが、迂回されても通らないようにここでも見る）
            'desired_delivery_date' => ['required', 'date', 'after:today'],
            'quantities' => ['array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ], [
            'desired_delivery_date.after' => '納入希望日は明日以降の日付を選んでください。',
        ], [
            'supplier_id' => '発注業者',
            'requester_name' => '発注者の氏名',
            'note' => '備考',
            'supplier_note' => '業者への連絡事項',
            'desired_delivery_date' => '納入希望日',
        ]);
    }

    /**
     * 入力された数量から、発注明細（申請時点のスナップショット）を組み立てる。
     * 新規申請・再申請で共通。フォームを細工されても通らないよう、ここでも
     * 「選んだ業者の資材か」「最低ロット以上か」を検証する。
     */
    private function buildItemSnapshots(array $validated): array
    {
        // 数量が1以上の資材だけを対象にする
        $selected = collect($validated['quantities'] ?? [])
            ->filter(fn ($qty) => (int) $qty > 0);

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'quantities' => '少なくとも1つの資材に数量を入力してください。',
            ]);
        }

        // 1申請＝1業者なので、選んだ業者の資材だけに限定する
        $materials = Material::with(['supplier', 'category', 'unit'])
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

        // 最低ロットがある資材は、その数量以上であれば端数でも発注できる（画面側でも弾いている）
        $lotErrors = [];
        $items = [];

        foreach ($selected as $materialId => $qty) {
            $material = $materials->get($materialId);
            if (! $material) {
                continue; // 無効化された資材・他業者の資材はスキップ
            }

            $lot = $material->min_lot_qty;

            if ($lot && (int) $qty < $lot) {
                $lotErrors[] = sprintf(
                    '「%s」は %s%s 以上で発注してください（入力値：%s）。',
                    $material->name,
                    number_format($lot),
                    $material->unit?->name ?? '',
                    number_format((int) $qty),
                );

                continue;
            }

            $items[] = $material->toOrderItemSnapshot() + ['quantity' => (int) $qty];
        }

        if ($lotErrors !== []) {
            throw ValidationException::withMessages(['quantities' => $lotErrors]);
        }

        return $items;
    }

    /**
     * 発注後メモを更新する（発注済のみ・総務/管理者）。
     * 業者から言われたこと等を残す自由記入メモ。閲覧は全員だが更新はここで絞る。
     */
    public function updatePostOrderNote(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canUpdatePostOrderNote($user), 403, '発注者メモを更新する権限がありません。');

        $validated = $request->validate([
            'post_order_note' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'post_order_note' => '発注者メモ',
        ]);

        $order->update([
            'post_order_note' => $validated['post_order_note'] ?? null,
            'post_order_note_updated_by' => $user->id,
            'post_order_note_updated_at' => now(),
        ]);

        return redirect()->route('orders.show', $order)
            ->with('status', '発注者メモを更新しました。');
    }

    /** 発注申請の詳細 */
    public function show(Request $request, Order $order): View
    {
        $this->authorizeView($request, $order);

        $order->load(['office', 'supplier', 'requester', 'managerApprover', 'reviewer', 'rejectedBy', 'returnedBy', 'orderedBy', 'postOrderNoteUpdatedBy', 'items.material']);

        $user = $request->user();

        // この画面で実行できるアクション（判定は Order のメソッドに集約）
        $actions = [
            'managerApprove' => $order->canBeManagerApprovedBy($user),
            'affairsApprove' => $order->canBeAffairsApprovedBy($user),
            'specialApprove' => $order->canBeSpecialApprovedBy($user),
            'return' => $order->canBeReturnedBy($user),
            'reject' => $order->canBeRejectedBy($user),
            'edit' => $order->canBeEditedBy($user),
            'delete' => $order->canBeDeletedBy($user),
            'updatePostOrderNote' => $order->canUpdatePostOrderNote($user),
        ];

        return view('orders.show', [
            'order' => $order,
            'actions' => $actions,
            'backUrl' => $this->backUrl($request),
        ]);
    }

    /** 「一覧に戻る」先＝直前の検索結果（メールのリンクなどから直接来た場合は素の一覧） */
    private function backUrl(Request $request): string
    {
        return $this->lastSearchUrl($request, 'orders.index');
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
