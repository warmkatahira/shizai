@extends('layouts.app')

@section('title', '資材マスタ管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">資材マスタ管理</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.materials.export', $filters) }}" data-no-loader
               class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md">📥 CSVダウンロード</a>
            <a href="{{ route('admin.materials.create') }}"
               class="bg-accent hover:bg-accent-dark text-ink text-sm px-4 py-2 rounded-md">＋ 新規資材</a>
        </div>
    </div>

    @include('admin.partials.errors')

    @include('admin.partials.csv-panel', [
        'label' => '資材',
        'importUrl' => route('admin.materials.import'),
        'extraNotes' => [
            'カテゴリ・発注業者・単位は<span class="font-medium">名前</span>で書きます。マスタに無い名前があるとエラーになります（先にカテゴリ・業者・単位を登録してください）。',
        ],
    ])

    {{-- 絞り込み。条件を変えると自動検索。CSVダウンロードは下のリンクに条件が引き継がれる --}}
    <form method="GET" action="{{ route('admin.materials.index') }}" data-auto-submit
          class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                <label class="block text-xs text-gray-500 mb-1">状態</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
                    <option value="">すべて</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>有効</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>無効</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">品名キーワード</label>
                <input autocomplete="off" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="例：段ボール"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-accent-dark">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            {{-- 条件を変えると自動で検索される。ボタンはJSが動かないときの保険 --}}
            <noscript>
                <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">検索</button>
            </noscript>
            <a href="{{ route('admin.materials.index') }}"
               class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-md">
                <span aria-hidden="true">✕</span> 条件クリア
            </a>
            <span class="ml-auto text-sm text-gray-500">{{ number_format($materials->count()) }} 件</span>
        </div>
    </form>

    {{-- いま効いている絞り込み。状態は初期値で「有効」が入るので、タグで見えるようにする --}}
    @php
        $chips = [];

        if (filled($filters['status'] ?? null)) {
            $chips[] = [
                'label' => '状態：' . ($filters['status'] === 'active' ? '有効' : '無効'),
                'remove' => ['status' => ''],
            ];
        }

        if (filled($filters['supplier_id'] ?? null)) {
            $chips[] = [
                'label' => '業者：' . ($suppliers->firstWhere('id', (int) $filters['supplier_id'])?->name ?? $filters['supplier_id']),
                'remove' => ['supplier_id' => ''],
            ];
        }

        if (filled($filters['category_id'] ?? null)) {
            $chips[] = [
                'label' => 'カテゴリ：' . ($categories->firstWhere('id', (int) $filters['category_id'])?->name ?? $filters['category_id']),
                'remove' => ['category_id' => ''],
            ];
        }

        if (filled($filters['keyword'] ?? null)) {
            $chips[] = ['label' => '品名：' . $filters['keyword'], 'remove' => ['keyword' => '']];
        }
    @endphp

    @include('partials.filter-chips', ['chips' => $chips])

    <div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
        <table class="w-full text-sm whitespace-nowrap">
            {{-- スクロールしても列名が見えるようヘッダー行を固定する --}}
            <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-10 shadow-[0_1px_0_0_var(--color-gray-200)]">
                <tr>
                    <th class="px-4 py-3">品名</th>
                    <th class="px-4 py-3">カテゴリ</th>
                    <th class="px-4 py-3">発注業者</th>
                    <th class="px-4 py-3 text-right">縦(mm)</th>
                    <th class="px-4 py-3 text-right">横(mm)</th>
                    <th class="px-4 py-3 text-right">高さ(mm)</th>
                    {{-- 3辺計は縦横高の合計。列には持たず計算して出す（DescribesMaterial::girthMm）--}}
                    <th class="px-4 py-3 text-right">3辺計(mm)</th>
                    <th class="px-4 py-3">発送時サイズ</th>
                    <th class="px-4 py-3">サイズ</th>
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
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 font-medium">
                            <div class="flex items-center gap-3">
                                @if ($material->imageUrl())
                                    <img src="{{ $material->imageUrl() }}" alt="{{ $material->name }}" data-zoom
                                         class="w-10 h-10 object-cover rounded-md border border-gray-200 shrink-0 cursor-zoom-in transition hover:opacity-80">
                                @else
                                    <span class="grid place-items-center w-10 h-10 rounded-md bg-gray-100 text-gray-300 shrink-0" aria-hidden="true">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 15.75h.008v.008H18v-.008ZM2.25 6a3.75 3.75 0 0 1 3.75-3.75h12A3.75 3.75 0 0 1 21.75 6v12a3.75 3.75 0 0 1-3.75 3.75h-12A3.75 3.75 0 0 1 2.25 18V6Z" /></svg>
                                    </span>
                                @endif
                                <span>
                                    {{ $material->name }}
                                    @if ($material->note)
                                        <span class="block text-xs text-gray-400 font-normal">{{ Str::limit($material->note, 30) }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $material->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->length_mm ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->width_mm ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->height_mm ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $material->girthText() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->shipping_size ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $material->size_text ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $material->unit?->name ?? '—' }}</td>
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
                    <tr><td colspan="15" class="px-4 py-8 text-center text-gray-400">資材がまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
