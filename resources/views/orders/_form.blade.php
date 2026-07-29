{{--
    発注申請の入力フォーム。新規申請（create）と、差し戻された申請の再申請（edit）で共通。

    渡すもの：
      $switchUrl   … 発注業者プルダウンを変えたときに開き直すURL
      $formAction  … 送信先URL
      $formMethod  … 'POST'（新規）/ 'PUT'（再申請）
      $submitLabel … 送信ボタンの文言
      $confirmMessage … 送信前の確認ダイアログの文言（誤送信を防ぐため。承認・却下と同じ作り）
      $suppliers / $supplier / $materials … 業者の選択肢・選択中の業者・その業者の資材
      $quantities  … 数量の初期値（material_id => 数量）。新規は空
      $order       … 再申請のときだけ渡す（入力の初期値に使う）
--}}
@php
    $order = $order ?? null;
@endphp

@include('admin.partials.errors')

{{-- ステップ1：発注業者を選ぶ（選ぶと、その業者の資材一覧に切り替わる） --}}
<form method="GET" action="{{ $switchUrl }}" class="bg-white shadow rounded-lg p-6 mb-6 max-w-xl">
    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">
        発注業者 <span class="text-red-500">*</span>
    </label>
    <select id="supplier_id" name="supplier_id" onchange="this.form.submit()"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <option value="">（業者を選択してください）</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" {{ $supplier?->id === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
        @endforeach
    </select>
    @if ($suppliers->isEmpty())
        <p class="text-sm text-amber-600 mt-2">発注できる資材を持つ業者が登録されていません。管理者に依頼してください。</p>
    @endif
    @if ($order)
        <p class="text-xs text-gray-400 mt-2">業者を変えると、入力した数量はいったんクリアされます。</p>
    @endif
    <noscript>
        <button type="submit" class="mt-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-md">資材を表示</button>
    </noscript>
</form>

@if ($supplier)
    @if ($materials->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-500">
            「{{ $supplier->name }}」に発注できる資材が登録されていません。
        </div>
    @else
        {{-- 送信は取り消せない（次の承認者へメールが飛ぶ）ので、承認・却下と同じく一度確認する --}}
        <form method="POST" action="{{ $formAction }}"
              onsubmit="return confirm(@js($confirmMessage))">
            @csrf
            @if ($formMethod === 'PUT')
                @method('PUT')
            @endif
            <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">

            {{-- ステップ2：数量を入力。最低ロットがある資材はロットの倍数でしか入力できない --}}
            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                {{-- 資材が多いと探すのが大変なので、その場で絞り込めるようにする。
                     どちらの入力欄にも name を付けていないので、申請の内容としては送信されない --}}
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium">
                        {{ $supplier->name }} の資材（<span data-visible-count>{{ $materials->count() }}</span>件）
                    </span>
                    <div class="ml-auto flex items-center gap-3">
                        <input type="search" autocomplete="off" data-material-filter placeholder="品名で絞り込む"
                               class="w-52 rounded-md border border-gray-300 px-3 py-1.5 text-sm outline-none focus:border-accent-dark">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 whitespace-nowrap">
                            <input type="checkbox" data-only-selected class="rounded border-gray-300">
                            入力済みのみ
                        </label>
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr>
                            <th class="px-4 py-3">品名</th>
                            <th class="px-4 py-3">カテゴリ</th>
                            <th class="px-4 py-3">寸法(mm)</th>
                            <th class="px-4 py-3 text-right">単価</th>
                            <th class="px-4 py-3 text-right">最低ロット</th>
                            <th class="px-4 py-3">単位</th>
                            <th class="px-4 py-3 w-48">数量</th>
                            <th class="px-4 py-3 text-right w-28">小計</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($materials as $material)
                            @php
                                $lot = $material->min_lot_qty;
                                // 入力エラーで戻ってきたときは old、そうでなければ元の申請の数量
                                $qty = old('quantities.' . $material->id, $quantities[$material->id] ?? '');
                            @endphp
                            {{-- data-name は絞り込み用（品名とカテゴリのどちらでも引けるようにしておく） --}}
                            <tr data-name="{{ $material->name }} {{ $material->category?->name }}">
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-3">
                                        @if ($material->imageUrl())
                                            <img src="{{ $material->imageUrl() }}" alt="{{ $material->name }}" data-zoom
                                                 class="w-10 h-10 object-cover rounded-md border border-gray-200 shrink-0 cursor-zoom-in transition hover:opacity-80">
                                        @else
                                            <span class="grid place-items-center w-10 h-10 rounded-md bg-gray-100 text-gray-300 shrink-0" aria-hidden="true">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 15.75h.008v.008H18v-.008ZM2.25 6a3.75 3.75 0 0 1 3.75-3.75h12A3.75 3.75 0 0 1 21.75 6v12a3.75 3.75 0 0 1-3.75 3.75h-12A3.75 3.75 0 0 1 2.25 18V6Z" /></svg>
                                            </span>
                                        @endif
                                        <span>{{ $material->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->sizeText() ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($material->unit_price) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $material->minLotText() ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->unit?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    {{-- ロット単位で増減するボタン。手打ちでロット違反を起こしにくくする --}}
                                    <div class="flex items-center gap-1">
                                        <button type="button" data-step="-1" tabindex="-1" aria-label="減らす"
                                                class="grid place-items-center w-7 h-7 shrink-0 rounded-md border border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-ink">−</button>
                                        <input autocomplete="off" type="number" min="0" max="999999"
                                               step="{{ $lot ?: 1 }}"
                                               name="quantities[{{ $material->id }}]"
                                               value="{{ $qty }}"
                                               data-qty
                                               data-price="{{ $material->unit_price ?? 0 }}"
                                               data-lot="{{ $lot ?: 0 }}"
                                               class="w-24 rounded-md border border-gray-300 px-2 py-1 text-right focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                                        <button type="button" data-step="1" tabindex="-1" aria-label="増やす"
                                                class="grid place-items-center w-7 h-7 shrink-0 rounded-md border border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-ink">＋</button>
                                    </div>
                                    @if ($lot)
                                        <span class="block text-xs text-gray-400 mt-1">{{ number_format($lot) }}{{ $material->unit?->name }}単位</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right" data-subtotal>—</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="7" class="px-4 py-3 text-right font-medium">合計</td>
                            <td class="px-4 py-3 text-right font-bold" data-total>¥0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- 資材が多いと表の一番下までスクロールしないと合計が見えないので、画面下に貼り付けておく。
                 中身は下の recalc() が表のtfootと一緒に書き換える --}}
            <div class="sticky bottom-4 z-20 mb-6 flex items-center justify-between gap-4 rounded-xl
                        bg-white/95 backdrop-blur ring-1 ring-ink/5 shadow-lg px-5 py-3">
                <span class="text-sm text-gray-500">
                    <span class="tabular-nums font-medium text-ink" data-selected-count>0</span> 品目を選択中
                </span>
                <span class="text-sm text-gray-500">
                    合計
                    <span class="ml-2 text-xl font-bold text-ink tabular-nums" data-total>¥0</span>
                </span>
            </div>

            {{-- ステップ3：申請者・納期・連絡事項 --}}
            <div class="bg-white shadow rounded-lg p-6 mb-6 max-w-2xl space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="requester_name" class="block text-sm font-medium text-gray-700 mb-1">
                            発注者の氏名 <span class="text-red-500">*</span>
                        </label>
                        @php
                            // 所長は個人のアカウントなので氏名を初期値に入れる。
                            // 申請用アカウントは営業所で共通なので空にして、実際に発注する人に入力してもらう。
                            $defaultRequesterName = $order?->requester_name
                                ?? (auth()->user()->is_manager ? auth()->user()->name : '');
                        @endphp
                        <input autocomplete="off" id="requester_name" name="requester_name" type="text" required
                               value="{{ old('requester_name', $defaultRequesterName) }}" placeholder="例：山田 太郎"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                        <p class="text-xs text-gray-400 mt-1">営業所で共通のアカウントを使うため、実際に発注する方の氏名を入れてください。</p>
                    </div>
                    <div>
                        <label for="desired_delivery_date" class="block text-sm font-medium text-gray-700 mb-1">
                            納入希望日 <span class="text-red-500">*</span>
                        </label>
                        @php
                            // 当日納品は業者の締めに間に合わないので、明日以降しか選べないようにする
                            $earliestDelivery = now()->addDay()->format('Y-m-d');
                            $deliveryDate = old('desired_delivery_date', $order?->desired_delivery_date?->format('Y-m-d'));
                            // 差し戻しの再申請では、元の希望日がもう過去になっていることがある。その場合は空にする
                            $deliveryDate = $deliveryDate && $deliveryDate >= $earliestDelivery ? $deliveryDate : '';
                        @endphp
                        <input autocomplete="off" id="desired_delivery_date" name="desired_delivery_date" type="date" required
                               min="{{ $earliestDelivery }}"
                               value="{{ $deliveryDate }}"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                        <p class="text-xs text-gray-400 mt-1">明日（{{ now()->addDay()->format('n/j') }}）以降を選んでください。この申請の全品目に適用され、発注書に印字されます。</p>
                    </div>
                </div>

                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">備考（社内向け・任意）</label>
                    <textarea id="note" name="note" rows="2"
                              class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none"
                              placeholder="所長・総務への連絡事項があれば記入してください">{{ old('note', $order?->note) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">社内用のメモです。発注書には印字されません。</p>
                </div>

                <div>
                    <label for="supplier_note" class="block text-sm font-medium text-gray-700 mb-1">業者への連絡事項（任意）</label>
                    <textarea id="supplier_note" name="supplier_note" rows="2"
                              class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none"
                              placeholder="例：送状備考欄に「○○○分」と明記お願いします。">{{ old('supplier_note', $order?->supplier_note) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">発注書の【備考欄】にそのまま印字されます。業者が読む文章です。</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-6 py-2 rounded-md">{{ $submitLabel }}</button>
                <a href="{{ $cancelUrl }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
            </div>
        </form>

        {{-- 数量を入れるたびに小計・合計を計算し、ロットの倍数でない入力をその場で知らせる --}}
        <script>
            (function () {
                const yen = (v) => '¥' + (Math.round(v * 100) / 100).toLocaleString('ja-JP');
                const inputs = document.querySelectorAll('[data-qty]');
                // 合計は表の下（tfoot）と画面下の固定バーの2か所にあるので、まとめて書き換える
                const totalCells = document.querySelectorAll('[data-total]');
                const countCells = document.querySelectorAll('[data-selected-count]');

                function recalc() {
                    let total = 0;
                    let selected = 0;

                    inputs.forEach((input) => {
                        const qty = parseInt(input.value, 10) || 0;
                        const price = parseFloat(input.dataset.price) || 0;
                        const lot = parseInt(input.dataset.lot, 10) || 0;
                        const row = input.closest('tr');
                        const cell = row.querySelector('[data-subtotal]');

                        // ロットの倍数でなければ申請できないので、その場で赤くして知らせる
                        const invalid = lot > 0 && qty > 0 && qty % lot !== 0;
                        input.classList.toggle('border-red-500', invalid);
                        input.setCustomValidity(
                            invalid ? `${lot.toLocaleString('ja-JP')} の倍数で入力してください。` : ''
                        );

                        // 数量を入れた行は地色を変えて、何を選んだか一目で分かるようにする
                        row.classList.toggle('qty-row-on', qty > 0 && !invalid);

                        if (qty > 0 && !invalid) {
                            const subtotal = price * qty;
                            total += subtotal;
                            selected++;
                            cell.textContent = yen(subtotal);
                            cell.classList.remove('text-gray-400');
                        } else {
                            cell.textContent = invalid ? 'ロット違反' : '—';
                            cell.classList.toggle('text-gray-400', !invalid);
                            cell.classList.toggle('text-red-600', invalid);
                        }
                    });

                    totalCells.forEach((cell) => (cell.textContent = yen(total)));
                    countCells.forEach((cell) => (cell.textContent = selected.toLocaleString('ja-JP')));
                }

                inputs.forEach((input) => input.addEventListener('input', recalc));
                recalc(); // 入力エラーで戻ってきたときや、再申請で数量が入っているときも計算し直す

                // ── ロット単位の増減ボタン ────────────────────────────────
                // 最低ロットがある資材は、その倍数だけ増減する（手打ちでの違反を減らす）
                document.querySelectorAll('[data-step]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const input = button.closest('td').querySelector('[data-qty]');
                        const lot = parseInt(input.dataset.lot, 10) || 1;
                        const current = parseInt(input.value, 10) || 0;
                        const next = current + lot * parseInt(button.dataset.step, 10);

                        input.value = Math.min(999999, Math.max(0, next)) || '';
                        recalc();
                    });
                });

                // ── 品名での絞り込み ──────────────────────────────────────
                // 行の表示/非表示を切り替えるだけ。隠した行の数量はそのまま送信される
                const filterBox = document.querySelector('[data-material-filter]');
                const onlySelected = document.querySelector('[data-only-selected]');
                const visibleCount = document.querySelector('[data-visible-count]');
                const rows = document.querySelectorAll('tbody tr[data-name]');

                function applyFilter() {
                    const keyword = (filterBox.value || '').trim().toLowerCase();
                    let visible = 0;

                    rows.forEach((row) => {
                        const hitKeyword = ! keyword || row.dataset.name.toLowerCase().includes(keyword);
                        const hasQty = (parseInt(row.querySelector('[data-qty]').value, 10) || 0) > 0;
                        const show = hitKeyword && (! onlySelected.checked || hasQty);

                        row.classList.toggle('hidden', ! show);
                        if (show) visible++;
                    });

                    visibleCount.textContent = visible.toLocaleString('ja-JP');
                }

                filterBox.addEventListener('input', applyFilter);
                onlySelected.addEventListener('change', applyFilter);
                // 「入力済みのみ」表示中に数量を消したら、その行もすぐ消えるように
                inputs.forEach((input) => input.addEventListener('input', () => {
                    if (onlySelected.checked) applyFilter();
                }));

                // ── 入力したまま画面を離れようとしたら確認する ────────────────
                let dirty = false;
                const orderForm = document.querySelector('[data-qty]').closest('form');

                inputs.forEach((input) => input.addEventListener('input', () => (dirty = true)));
                document.querySelectorAll('[data-step]').forEach((b) => b.addEventListener('click', () => (dirty = true)));
                orderForm.addEventListener('submit', () => (dirty = false)); // 送信するときは当然出さない

                window.addEventListener('beforeunload', (e) => {
                    if (dirty) {
                        e.preventDefault();
                        e.returnValue = ''; // 文言はブラウザ側が決める（指定しても表示されない）
                    }
                });
            })();
        </script>
    @endif
@endif
