@extends('layouts.app')

@section('title', '資材マスタ管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">資材マスタ管理</h1>
        <a href="{{ route('admin.materials.create') }}"
           class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規資材</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm whitespace-nowrap">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">品名</th>
                    <th class="px-4 py-3">カテゴリ</th>
                    <th class="px-4 py-3">発注業者</th>
                    <th class="px-4 py-3">寸法(mm)</th>
                    <th class="px-4 py-3">単位</th>
                    <th class="px-4 py-3 text-right">単価</th>
                    <th class="px-4 py-3 text-right">最低ロット</th>
                    <th class="px-4 py-3">名入れ</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($materials as $material)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $material->name }}
                            @if ($material->note)
                                <span class="block text-xs text-gray-400 font-normal">{{ Str::limit($material->note, 30) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $material->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->sizeText() ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $material->unit }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($material->unit_price) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->minLotText() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->has_imprint ? 'あり' : '—' }}</td>
                        <td class="px-4 py-3">
                            @include('admin.partials.status-badge', ['active' => $material->is_active])
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.materials.edit', $material) }}" class="text-accent-strong hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.materials.destroy', $material) }}" class="inline"
                                  onsubmit="return confirm('「{{ $material->name }}」を削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">資材がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
