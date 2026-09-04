@extends('layouts.app')

{{-- 閲覧だけの人には「管理」と言わない --}}
@section('title', auth()->user()->canManageMasters() ? '単位マスタ管理' : '単位マスタ')

@section('content')
    @php
        // 登録・編集・削除・CSVは管理者・総務だけ。
        // それ以外の権限は「表示するマスタ」でオンにされて見ているので、一覧を読むだけ
        $canEditMasters = auth()->user()->canManageMasters();
    @endphp
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">{{ $canEditMasters ? '単位マスタ管理' : '単位マスタ' }}</h1>
        @if ($canEditMasters)
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.units.export') }}" data-no-loader
               class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md">📥 CSVダウンロード</a>
            <a href="{{ route('admin.units.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規単位</a>
        </div>
        @endif
    </div>

    @include('admin.partials.errors')

    @if ($canEditMasters)
    @include('admin.partials.csv-panel', [
        'label' => '単位',
        'importUrl' => route('admin.units.import'),
        'extraNotes' => [
            '単位名は資材CSVから引くキーです。<span class="font-medium">名前を変えると</span>、その名前で書かれた資材CSVは取り込めなくなります。',
        ],
    ])
    @endif

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">単位名</th>
                    <th class="px-4 py-3 text-right">表示順</th>
                    <th class="px-4 py-3 text-right">資材数</th>
                    <th class="px-4 py-3">状態</th>
                    @if ($canEditMasters)
                        <th class="px-4 py-3 text-right">操作</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($units as $unit)
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $unit->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $unit->sort_order }}</td>
                        <td class="px-4 py-3 text-right">{{ $unit->materials_count }} 件</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $unit->is_active])
                        </td>
                        @if ($canEditMasters)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.units.edit', $unit) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" class="inline"
                                  onsubmit="return confirm('「{{ $unit->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canEditMasters ? 5 : 4 }}" class="px-4 py-8 text-center text-gray-400">単位がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
