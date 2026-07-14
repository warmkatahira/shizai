@extends('layouts.app')

@section('title', '業者マスタ管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">業者マスタ管理</h1>
        <a href="{{ route('admin.suppliers.create') }}"
           class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規業者</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">業者名</th>
                    <th class="px-4 py-3">コード</th>
                    <th class="px-4 py-3">担当者</th>
                    <th class="px-4 py-3">発注方法</th>
                    <th class="px-4 py-3">電話</th>
                    <th class="px-4 py-3 text-right">取扱資材数</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $supplier->code ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $supplier->contact_person ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $supplier->orderMethodLabel() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $supplier->phone ?: '—' }}
                            <span class="block text-xs text-gray-400">FAX {{ $supplier->fax ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ $supplier->materials_count }} 件</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $supplier->is_active])
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="inline"
                                  onsubmit="return confirm('「{{ $supplier->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">業者がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
