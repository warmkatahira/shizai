@extends('layouts.app')

@section('title', '発注集計')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">
            発注集計
            <span class="text-sm font-normal text-gray-500">（発注済のみ）</span>
            @if (auth()->user()->isSales() && auth()->user()->office)
                <span class="text-sm font-normal text-gray-500">／ {{ auth()->user()->office->name }}</span>
            @endif
        </h1>
        <a href="{{ route('reports.export', request()->query()) }}"
           class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-md">CSVダウンロード</a>
    </div>

    {{-- 集計条件 --}}
    <form method="GET" action="{{ route('reports.index') }}" class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">集計軸</label>
                <select name="axis" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    @foreach ($axes as $value => $label)
                        <option value="{{ $value }}" {{ $axis === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($offices->isNotEmpty())
                <div>
                    <label class="block text-xs text-gray-500 mb-1">営業所</label>
                    <select name="office_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                        <option value="">すべて</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" {{ (string) ($filters['office_id'] ?? '') === (string) $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-xs text-gray-500 mb-1">カテゴリ</label>
                <select name="category_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">業者</label>
                <select name="supplier_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) ($filters['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">発注日（開始）</label>
                <input autocomplete="off" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">発注日（終了）</label>
                <input autocomplete="off" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">集計する</button>
            <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:underline">条件クリア</a>
        </div>
    </form>

    {{-- サマリー --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500">発注件数</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($totals->order_count) }} <span class="text-sm font-normal text-gray-400">件</span></div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500">数量合計</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($totals->quantity) }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500">金額合計（参考）</div>
            <div class="text-2xl font-bold mt-1">{{ \App\Support\Money::yen($totals->amount, '¥0') }}</div>
        </div>
    </div>

    {{-- 集計結果 --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">{{ rtrim($axisLabel, '別') }}</th>
                    <th class="px-4 py-3 text-right">発注件数</th>
                    <th class="px-4 py-3 text-right">明細数</th>
                    <th class="px-4 py-3 text-right">数量合計</th>
                    <th class="px-4 py-3 text-right">金額合計</th>
                    <th class="px-4 py-3 w-48">構成比（金額）</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    @php $share = $totals->amount > 0 ? $row->amount / $totals->amount * 100 : 0; @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $row->label }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format($row->order_count) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format($row->item_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row->quantity) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ \App\Support\Money::yen($row->amount) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-100 rounded overflow-hidden">
                                    <div class="h-2 bg-accent-dark" style="width: {{ number_format($share, 1) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-12 text-right">{{ number_format($share, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">該当する発注実績がありません。</td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-4 py-3 font-medium">合計</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->order_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->item_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->quantity) }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Money::yen($totals->amount) }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        ※ 集計対象は「発注済」の申請のみ。期間は発注日（総務が発注を確定した日）で絞り込みます。
        金額は申請時点の参考単価 × 数量です。
    </p>
@endsection
