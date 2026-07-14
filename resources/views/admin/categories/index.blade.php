@extends('layouts.app')

@section('title', 'カテゴリマスタ管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">カテゴリマスタ管理</h1>
        <a href="{{ route('admin.categories.create') }}"
           class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規カテゴリ</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">カテゴリ名</th>
                    <th class="px-4 py-3 text-right">表示順</th>
                    <th class="px-4 py-3 text-right">資材数</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3 text-right">{{ $category->materials_count }} 件</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $category->is_active])
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline"
                                  onsubmit="return confirm('「{{ $category->name }}」を削除しますか？このカテゴリの資材は「未設定」になります。')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">カテゴリがまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
