@extends('layouts.app')

@section('title', '資材一覧')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">
            資材一覧
            <span class="text-sm font-normal text-gray-500">（閲覧のみ）</span>
        </h1>
        @if (auth()->user()->isSales())
            <a href="{{ route('orders.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規発注申請</a>
        @endif
    </div>

    {{-- 絞り込み --}}
    <form method="GET" action="{{ route('materials.index') }}" class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">発注業者</label>
                <select name="supplier_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) ($filters['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">カテゴリ</label>
                <select name="category_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">品名キーワード</label>
                <input autocomplete="off" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="例：段ボール"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">検索</button>
            <a href="{{ route('materials.index') }}" class="text-sm text-gray-500 hover:underline">条件クリア</a>
            <span class="ml-auto text-sm text-gray-500">{{ number_format($materials->count()) }} 件</span>
        </div>
    </form>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">品名</th>
                    <th class="px-4 py-3">カテゴリ</th>
                    <th class="px-4 py-3">発注業者</th>
                    <th class="px-4 py-3">発注方法</th>
                    <th class="px-4 py-3">寸法(mm)</th>
                    <th class="px-4 py-3">単位</th>
                    <th class="px-4 py-3 text-right">単価</th>
                    <th class="px-4 py-3 text-right">最低ロット</th>
                    <th class="px-4 py-3">名入れ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($materials as $material)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $material->name }}
                            @if ($material->note)
                                <span class="block text-xs text-gray-400 font-normal">{{ Str::limit($material->note, 40) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $material->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->supplier?->orderMethodLabel() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->sizeText() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->unit }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($material->unit_price) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->minLotText() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->has_imprint ? 'あり' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">該当する資材がありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        ※ 有効な資材のみ表示しています。内容の変更は管理者にご依頼ください。
    </p>
@endsection
