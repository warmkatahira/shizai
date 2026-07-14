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

                {{-- ステップ2：数量を入力 --}}
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
                                <th class="px-4 py-3 w-32">数量</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($materials as $material)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $material->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->sizeText() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($material->unit_price) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ $material->minLotText() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->unit }}</td>
                                    <td class="px-4 py-3">
                                        <input autocomplete="off" type="number" min="0" max="9999"
                                               name="quantities[{{ $material->id }}]"
                                               value="{{ old('quantities.' . $material->id) }}"
                                               class="w-24 rounded-md border border-gray-300 px-2 py-1 text-right focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ステップ3：申請者・納期・連絡事項 --}}
                <div class="bg-white shadow rounded-lg p-6 mb-6 max-w-2xl space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="requester_name" class="block text-sm font-medium text-gray-700 mb-1">
                                発注者の氏名 <span class="text-red-500">*</span>
                            </label>
                            <input autocomplete="off" id="requester_name" name="requester_name" type="text" required
                                   value="{{ old('requester_name') }}" placeholder="例：山田 太郎"
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
                                  placeholder="例：送状備考欄に「ラッド分」と明記お願いします。">{{ old('supplier_note') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">発注書の【備考欄】にそのまま印字されます。業者が読む文章です。</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-6 py-2 rounded-md">申請する</button>
                    <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
                </div>
            </form>
        @endif
    @endif
@endsection
