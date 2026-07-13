@extends('layouts.app')

@section('title', '営業所管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">営業所管理</h1>
        <a href="{{ route('admin.offices.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md">＋ 新規営業所</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">営業所名</th>
                    <th class="px-4 py-3">コード</th>
                    <th class="px-4 py-3">所属人数</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($offices as $office)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $office->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $office->code ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $office->users_count }} 名</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $office->is_active])
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.offices.edit', $office) }}" class="text-indigo-600 hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.offices.destroy', $office) }}" class="inline"
                                  onsubmit="return confirm('「{{ $office->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">営業所がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
