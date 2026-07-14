@extends('layouts.app')

@section('title', '発注申請一覧')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">
            発注申請一覧
            @if (auth()->user()->isSales() && auth()->user()->office)
                <span class="text-sm font-normal text-gray-500">（{{ auth()->user()->office->name }}）</span>
            @endif
        </h1>
        @if (auth()->user()->isSales())
            <a href="{{ route('orders.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規発注申請</a>
        @endif
    </div>

    {{-- 検索・絞り込み --}}
    <form method="GET" action="{{ route('orders.index') }}" data-auto-submit
          class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">ステータス</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                <label class="block text-xs text-gray-500 mb-1">業者</label>
                <select name="supplier_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) ($filters['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">品名キーワード</label>
                <input autocomplete="off" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="例：用紙"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">申請日（開始）</label>
                <input autocomplete="off" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">申請日（終了）</label>
                <input autocomplete="off" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">検索</button>
            <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:underline">条件クリア</a>
            <a href="{{ route('orders.export', $filters) }}"
               class="ml-auto bg-green-600 hover:bg-green-700 text-white text-sm px-5 py-2 rounded-md">
                📥 CSVダウンロード
            </a>
        </div>
    </form>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">申請番号</th>
                    <th class="px-4 py-3">申請日</th>
                    @unless (auth()->user()->isSales())
                        <th class="px-4 py-3">営業所</th>
                    @endunless
                    <th class="px-4 py-3">発注業者</th>
                    <th class="px-4 py-3">発注者</th>
                    <th class="px-4 py-3 text-right">点数</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-medium">#{{ $order->id }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->created_at->format('Y/m/d H:i') }}</td>
                        @unless (auth()->user()->isSales())
                            <td class="px-4 py-3">{{ $order->office->name }}</td>
                        @endunless
                        <td class="px-4 py-3">{{ $order->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $order->requester_name ?? $order->requester->name }}</td>
                        <td class="px-4 py-3 text-right">{{ $order->items_count ?? $order->items->count() }} 点</td>
                        <td class="px-4 py-3">@include('orders.partials.status-badge')</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-accent-strong hover:underline">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">発注申請がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
