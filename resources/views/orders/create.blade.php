@extends('layouts.app')

@section('title', '新規発注申請')

@section('content')
    <h1 class="text-xl font-bold mb-2">新規発注申請</h1>
    <p class="text-sm text-gray-500 mb-6">
        発注業者を選び、必要な資材の数量を入力して申請してください。1回の申請につき業者は1社です。
    </p>

    @include('admin.partials.errors')

    {{-- ステップ1：発注業者を選ぶ（選ぶと、その業者の資材一覧に切り替わる） --}}
    <form method="GET" action="{{ route('orders.create') }}" class="bg-white shadow rounded-lg p-6 mb-6 max-w-xl">
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
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">

                {{-- ステップ2：数量を入力。最低ロットがある資材はロットの倍数でしか入力できない --}}
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 text-sm font-medium">
                        {{ $supplier->name }} の資材（{{ $materials->count() }}件）
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
                                <th class="px-4 py-3 w-36">数量</th>
                                <th class="px-4 py-3 text-right w-28">小計</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($materials as $material)
                                @php $lot = $material->min_lot_qty; @endphp
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $material->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->sizeText() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($material->unit_price) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ $material->minLotText() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->unit }}</td>
                                    <td class="px-4 py-3">
                                        <input autocomplete="off" type="number" min="0" max="999999"
                                               step="{{ $lot ?: 1 }}"
                                               name="quantities[{{ $material->id }}]"
                                               value="{{ old('quantities.' . $material->id) }}"
                                               data-qty
                                               data-price="{{ $material->unit_price ?? 0 }}"
                                               data-lot="{{ $lot ?: 0 }}"
                                               class="w-28 rounded-md border border-gray-300 px-2 py-1 text-right focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                                        @if ($lot)
                                            <span class="block text-xs text-gray-400 mt-1">{{ number_format($lot) }}{{ $material->min_lot_unit }}単位</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right" data-subtotal>—</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-right font-medium">合計（参考）</td>
                                <td class="px-4 py-3 text-right font-bold" data-total>¥0</td>
                            </tr>
                        </tfoot>
                    </table>
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
                                $defaultRequesterName = auth()->user()->is_manager ? auth()->user()->name : '';
                            @endphp
                            <input autocomplete="off" id="requester_name" name="requester_name" type="text" required
                                   value="{{ old('requester_name', $defaultRequesterName) }}" placeholder="例：山田 太郎"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                            <p class="text-xs text-gray-400 mt-1">営業所で共通のアカウントを使うため、実際に発注する方の氏名を入れてください。</p>
                        </div>
                        <div>
                            <label for="desired_delivery_date" class="block text-sm font-medium text-gray-700 mb-1">納入希望日</label>
                            <input autocomplete="off" id="desired_delivery_date" name="desired_delivery_date" type="date"
                                   value="{{ old('desired_delivery_date') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                            <p class="text-xs text-gray-400 mt-1">この申請の全品目に適用され、発注書に印字されます。</p>
                        </div>
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 mb-1">備考（社内向け・任意）</label>
                        <textarea id="note" name="note" rows="2"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none"
                                  placeholder="所長・総務への連絡事項があれば記入してください">{{ old('note') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">社内用のメモです。発注書には印字されません。</p>
                    </div>

                    <div>
                        <label for="supplier_note" class="block text-sm font-medium text-gray-700 mb-1">業者への連絡事項（任意）</label>
                        <textarea id="supplier_note" name="supplier_note" rows="2"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none"
                                  placeholder="例：送状備考欄に「○○○分」と明記お願いします。">{{ old('supplier_note') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">発注書の【備考欄】にそのまま印字されます。業者が読む文章です。</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-6 py-2 rounded-md">申請する</button>
                    <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
                </div>
            </form>

            {{-- 数量を入れるたびに小計・合計を計算し、ロットの倍数でない入力をその場で知らせる --}}
            <script>
                (function () {
                    const yen = (v) => '¥' + (Math.round(v * 100) / 100).toLocaleString('ja-JP');
                    const inputs = document.querySelectorAll('[data-qty]');
                    const totalCell = document.querySelector('[data-total]');

                    function recalc() {
                        let total = 0;

                        inputs.forEach((input) => {
                            const qty = parseInt(input.value, 10) || 0;
                            const price = parseFloat(input.dataset.price) || 0;
                            const lot = parseInt(input.dataset.lot, 10) || 0;
                            const cell = input.closest('tr').querySelector('[data-subtotal]');

                            // ロットの倍数でなければ申請できないので、その場で赤くして知らせる
                            const invalid = lot > 0 && qty > 0 && qty % lot !== 0;
                            input.classList.toggle('border-red-500', invalid);
                            input.setCustomValidity(
                                invalid ? `${lot.toLocaleString('ja-JP')} の倍数で入力してください。` : ''
                            );

                            if (qty > 0 && !invalid) {
                                const subtotal = price * qty;
                                total += subtotal;
                                cell.textContent = yen(subtotal);
                                cell.classList.remove('text-gray-400');
                            } else {
                                cell.textContent = invalid ? 'ロット違反' : '—';
                                cell.classList.toggle('text-gray-400', !invalid);
                                cell.classList.toggle('text-red-600', invalid);
                            }
                        });

                        totalCell.textContent = yen(total);
                    }

                    inputs.forEach((input) => input.addEventListener('input', recalc));
                    recalc(); // 入力エラーで戻ってきたときも計算し直す
                })();
            </script>
        @endif
    @endif
@endsection
