@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">ユーザー管理</h1>
        <a href="{{ route('admin.users.create') }}"
           class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規ユーザー</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">氏名</th>
                    <th class="px-4 py-3">ログインID</th>
                    <th class="px-4 py-3">メールアドレス</th>
                    <th class="px-4 py-3">権限</th>
                    <th class="px-4 py-3">所属営業所</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $user->login_id }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->email ?: '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $user->roleLabel() }}
                            @if ($user->is_manager)
                                <span class="ml-1 inline-block px-1.5 py-0.5 rounded text-xs bg-accent-light text-accent-strong">所長</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->office?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $user->is_active])
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-accent-strong hover:underline">編集</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('「{{ $user->name }}」を削除しますか？')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline ml-2">削除</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">ユーザーがまだいません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
