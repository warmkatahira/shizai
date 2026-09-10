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
            {{-- 条件を変えると自動で検索される。ボタンはJSが動かないときの保険 --}}
            <noscript>
                <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">検索</button>
            </noscript>
            <a href="{{ route('orders.index') }}"
               class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-md">
                <span aria-hidden="true">✕</span> 条件クリア
            </a>
            <a href="{{ route('orders.export', $filters) }}" data-no-loader
               class="ml-auto bg-green-600 hover:bg-green-700 text-white text-sm px-5 py-2 rounded-md">
                📥 CSVダウンロード
            </a>
        </div>
    </form>

    {{-- いま効いている絞り込み。総務は初期値でステータスが入るので、何で絞られているか見えるようにする --}}
    @php
        $chips = [];

        if (filled($filters['status'] ?? null)) {
            $chips[] = [
                'label' => 'ステータス：' . ($statuses[$filters['status']] ?? $filters['status']),
                'remove' => ['status' => ''],
            ];
        }

        if (filled($filters['office_id'] ?? null)) {
            $chips[] = [
                'label' => '営業所：' . ($offices->firstWhere('id', (int) $filters['office_id'])?->name ?? $filters['office_id']),
                'remove' => ['office_id' => ''],
            ];
        }

        if (filled($filters['supplier_id'] ?? null)) {
            $chips[] = [
                'label' => '業者：' . ($suppliers->firstWhere('id', (int) $filters['supplier_id'])?->name ?? $filters['supplier_id']),
                'remove' => ['supplier_id' => ''],
            ];
        }

        if (filled($filters['keyword'] ?? null)) {
            $chips[] = ['label' => '品名：' . $filters['keyword'], 'remove' => ['keyword' => '']];
        }

        // 期間は開始・終了で1つのタグにまとめる（外すときも両方まとめて外す）
        if (filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null)) {
            $chips[] = [
                'label' => '申請日：' . ($filters['date_from'] ?? '') . '〜' . ($filters['date_to'] ?? ''),
                'remove' => ['date_from' => '', 'date_to' => ''],
            ];
        }
    @endphp

    @include('partials.filter-chips', ['chips' => $chips])

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            {{-- スクロールしても列名が見えるよう固定する。枠内スクロール（overflow-auto）なので
                 sticky の基準はこの枠。マスタ系と同じく top-0 で枠の上端に貼り付ける --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                {{-- 見出しを押すと並び替わる（判定・SQLは OrderController::applySort） --}}
                <tr>
                    @include('orders.partials.sort-header', ['key' => 'id', 'label' => '申請番号', 'default' => 'desc'])
                    @include('orders.partials.sort-header', ['key' => 'created_at', 'label' => '申請日', 'default' => 'desc'])
                    @include('orders.partials.sort-header', ['key' => 'desired_delivery_date', 'label' => '希望納期'])
                    @unless (auth()->user()->isSales())
                        @include('orders.partials.sort-header', ['key' => 'office', 'label' => '営業所'])
                    @endunless
                    @include('orders.partials.sort-header', ['key' => 'supplier', 'label' => '発注業者'])
                    @include('orders.partials.sort-header', ['key' => 'requester_name', 'label' => '申請者'])
                    @include('orders.partials.sort-header', ['key' => 'items_count', 'label' => '点数', 'default' => 'desc', 'align' => 'right'])
                    @include('orders.partials.sort-header', ['key' => 'status', 'label' => '状態'])
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    {{-- 行のどこを押しても詳細へ（処理は layouts/app.blade.php の共通スクリプト） --}}
                    <tr data-href="{{ route('orders.show', $order) }}"
                        class="cursor-pointer hover:bg-accent-light/40 transition-colors">
                        {{-- 直送は納入先が営業所と違うので、一覧でも分かるように印を付ける --}}
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            #{{ $order->id }}
                            @if ($order->isDirectShipping())
                                <span class="ml-1 inline-block rounded bg-amber-100 text-amber-800 text-xs px-1.5 py-0.5">直送</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->created_at->format('Y/m/d H:i') }}</td>
                        {{-- 希望納期。まだ発注していないもののうち、過ぎているもの・迫っているものだけ
                             色を付けて気づかせる（判定は Order::deliveryUrgency）。
                             発注済・却下は手を動かす必要が無いので色を付けない --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $urgency = $order->deliveryUrgency();
                            @endphp
                            @if ($order->desired_delivery_date)
                                <span class="{{ match ($urgency) {
                                    'over' => 'text-red-600 font-bold',
                                    'soon' => 'text-orange-600 font-bold',
                                    default => 'text-gray-500',
                                } }}">{{ $order->desired_delivery_date->format('Y/m/d') }}</span>
                                @if ($urgency === 'over')
                                    <span class="ml-1 text-xs text-red-600">超過</span>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
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
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">発注申請がありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->total() > 0)
        <div class="mt-4 flex items-center justify-between gap-4 text-sm text-gray-500">
            <span>
                全 {{ number_format($orders->total()) }} 件中
                {{ number_format($orders->firstItem()) }}〜{{ number_format($orders->lastItem()) }} 件を表示
            </span>
            {{-- 検索条件はページリンクに引き継がれる（withQueryString） --}}
            <div>{{ $orders->links() }}</div>
        </div>
    @endif
@endsection
