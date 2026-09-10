@extends('layouts.app')

{{-- 閲覧だけの人には「管理」と言わない --}}
@section('title', auth()->user()->canEditMaster('shipping_destinations') ? '直送先管理' : '直送先一覧')

@section('content')
    @php
        // このマスタを編集できるか（管理者・総務は常に可。それ以外はユーザー管理の「編集」トグル）。
        // オフの人は一覧を読むだけなので、登録・編集・削除・CSVは丸ごと出さない
        $canEditMaster = auth()->user()->canEditMaster('shipping_destinations');
    @endphp
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">{{ $canEditMaster ? '直送先管理' : '直送先一覧' }}</h1>
        @if ($canEditMaster)
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.shipping_destinations.export') }}" data-no-loader
               class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md">📥 CSVダウンロード</a>
            <a href="{{ route('admin.shipping_destinations.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規直送先</a>
        </div>
        @endif
    </div>

    <p class="text-sm text-gray-500 mb-6">
        発注申請で「直送」を選んだときの送り先です。全営業所で共通の一覧になります。
    </p>

    @include('admin.partials.errors')

    @if ($canEditMaster)
    @include('admin.partials.csv-panel', [
        'label' => '直送先',
        'importUrl' => route('admin.shipping_destinations.import'),
    ])
    @endif

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">直送先名</th>
                    <th class="px-4 py-3">住所</th>
                    <th class="px-4 py-3 whitespace-nowrap">電話 / FAX</th>
                    <th class="px-4 py-3 whitespace-nowrap">発注実績</th>
                    <th class="px-4 py-3">状態</th>
                    @if ($canEditMaster)
                        <th class="px-4 py-3 text-right">操作</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($destinations as $destination)
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $destination->name }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($destination->postal_code)
                                <span class="block text-xs text-gray-400">〒{{ $destination->postal_code }}</span>
                            @endif
                            {{ $destination->address }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $destination->tel ?: '—' }}
                            <span class="block text-xs text-gray-400">FAX {{ $destination->fax ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $destination->orders_count }} 件</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $destination->is_active])
                        </td>
                        @if ($canEditMaster)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.shipping_destinations.edit', $destination) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.shipping_destinations.destroy', $destination) }}" class="inline"
                                  onsubmit="return confirm('「{{ $destination->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canEditMaster ? 6 : 5 }}" class="px-4 py-8 text-center text-gray-400">直送先がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
