@extends('layouts.app')

{{-- 閲覧だけの人には「管理」と言わない --}}
@section('title', auth()->user()->canManageMasters() ? '営業所管理' : '営業所一覧')

@section('content')
    @php
        // 登録・編集・削除・CSVは管理者・総務だけ。
        // それ以外の権限は「表示するマスタ」でオンにされて見ているので、一覧を読むだけ
        $canEditMasters = auth()->user()->canManageMasters();
    @endphp
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">{{ $canEditMasters ? '営業所管理' : '営業所一覧' }}</h1>
        @if ($canEditMasters)
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.offices.export') }}" data-no-loader
               class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md">📥 CSVダウンロード</a>
            <a href="{{ route('admin.offices.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規営業所</a>
        </div>
        @endif
    </div>

    @include('admin.partials.errors')

    @if ($canEditMasters)
    @include('admin.partials.csv-panel', [
        'label' => '営業所',
        'importUrl' => route('admin.offices.import'),
    ])
    @endif

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">営業所名</th>
                    <th class="px-4 py-3">コード</th>
                    <th class="px-4 py-3">住所</th>
                    <th class="px-4 py-3 whitespace-nowrap">電話 / FAX</th>
                    <th class="px-4 py-3 whitespace-nowrap">所属人数</th>
                    <th class="px-4 py-3">状態</th>
                    @if ($canEditMasters)
                        <th class="px-4 py-3 text-right">操作</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($offices as $office)
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $office->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $office->code ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($office->address)
                                @if ($office->postal_code)
                                    <span class="block text-xs text-gray-400">〒{{ $office->postal_code }}</span>
                                @endif
                                {{ $office->address }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $office->tel ?: '—' }}
                            <span class="block text-xs text-gray-400">FAX {{ $office->fax ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $office->users_count }} 名</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $office->is_active])
                        </td>
                        @if ($canEditMasters)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.offices.edit', $office) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.offices.destroy', $office) }}" class="inline"
                                  onsubmit="return confirm('「{{ $office->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canEditMasters ? 7 : 6 }}" class="px-4 py-8 text-center text-gray-400">営業所がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
