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

    {{-- CSV取り込み。出したCSVをExcelで直して戻す使い方を想定している --}}
    <details class="bg-white shadow rounded-lg mb-6" {{ $errors->any() ? 'open' : '' }}>
        <summary class="px-6 py-4 cursor-pointer text-sm font-medium select-none">
            📤 CSVから取り込む（追加・更新）
        </summary>
        <div class="px-6 pb-6 pt-1 border-t border-gray-100">
            <ul class="text-xs text-gray-500 space-y-1 mb-4 list-disc list-inside">
                <li>まず <span class="font-medium">CSVダウンロード</span> で現在の資材を出し、Excelで直してから取り込むのが確実です。</li>
                <li><span class="font-medium">ID列</span>が入っている行はその資材を<span class="font-medium">更新</span>、ID列が空の行は<span class="font-medium">新規追加</span>になります。</li>
                <li>カテゴリ・発注業者は<span class="font-medium">名前</span>で書きます。マスタに無い名前があるとエラーになります（先にカテゴリ・業者を登録してください）。</li>
                <li><span class="font-medium">1行でもエラーがあれば、何も取り込みません。</span>行番号つきでエラーを表示します。</li>
                <li>CSVに書かなかった資材は、そのまま残ります（削除はされません）。消したい資材は「有効」を <span class="font-medium">いいえ</span> にしてください。</li>
            </ul>

            <form method="POST" action="{{ route('admin.materials.import') }}" enctype="multipart/form-data"
                  class="flex flex-wrap items-center gap-3"
                  onsubmit="return confirm('このCSVで資材マスタを追加・更新します。よろしいですか？')">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" required
                       class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:text-gray-700 hover:file:bg-gray-200">
                <button class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">取り込む</button>
            </form>
        </div>
    </details>

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
                    <tr class="hover:bg-accent-light/40 transition-colors">
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
