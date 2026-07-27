@extends('layouts.app')

@section('title', '操作ログ')

@php
    // 種別ごとのバッジ色。意味のある色は @theme で暖色寄りに置き換え済み
    $categoryStyle = [
        'order' => 'bg-accent-light text-accent-strong',
        'master' => 'bg-blue-100 text-blue-700',
        'user' => 'bg-amber-100 text-amber-700',
        'auth' => 'bg-gray-100 text-gray-600',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">操作ログ</h1>
        <span class="text-sm text-gray-400">誰が・いつ・何をしたかの記録（管理者のみ）</span>
    </div>

    {{-- 検索・絞り込み --}}
    <form method="GET" action="{{ route('admin.logs.index') }}" data-auto-submit
          class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">種別</label>
                <select name="category" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['category'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">操作者</label>
                <select name="user_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    {{-- 権限ごとにグループ分け。営業所は所長とそれ以外で分ける --}}
                    @foreach ($userGroups as $group)
                        <optgroup label="{{ $group['label'] }}">
                            @foreach ($group['users'] as $u)
                                <option value="{{ $u->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">内容キーワード</label>
                <input autocomplete="off" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="例：承認"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">日時（開始）</label>
                <input autocomplete="off" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">日時（終了）</label>
                <input autocomplete="off" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <noscript>
                <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">検索</button>
            </noscript>
            <a href="{{ route('admin.logs.index') }}"
               class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-md">
                <span aria-hidden="true">✕</span> 条件クリア
            </a>
            <a href="{{ route('admin.logs.export', $filters) }}" data-no-loader
               class="ml-auto bg-green-600 hover:bg-green-700 text-white text-sm px-5 py-2 rounded-md">
                📥 CSVダウンロード
            </a>
        </div>
    </form>

    <div class="bg-white shadow rounded-lg">
        <table class="w-full text-sm">
            {{-- ページごとスクロールする表なので、固定ヘッダー（h-16）に隠れないよう top-16 で止める --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-16 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">日時</th>
                    <th class="px-4 py-3 whitespace-nowrap">操作者</th>
                    <th class="px-4 py-3 whitespace-nowrap">種別</th>
                    <th class="px-4 py-3">内容</th>
                    <th class="px-4 py-3 whitespace-nowrap">営業所</th>
                    <th class="px-4 py-3 whitespace-nowrap">IPアドレス</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('Y/m/d H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->user_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $categoryStyle[$log->category()] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $log->categoryLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->office?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">該当する操作ログがありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->total() > 0)
        <div class="mt-4 flex items-center justify-between gap-4 text-sm text-gray-500">
            <span>
                全 {{ number_format($logs->total()) }} 件中
                {{ number_format($logs->firstItem()) }}〜{{ number_format($logs->lastItem()) }} 件を表示
            </span>
            <div>{{ $logs->links() }}</div>
        </div>
    @endif
@endsection
