<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">品名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $material->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">商品カテゴリ</label>
        <select id="category_id" name="category_id"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <option value="">（未設定）</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ (string) old('category_id', $material->category_id) === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @if ($categories->isEmpty())
            <p class="text-xs text-amber-600 mt-1">カテゴリマスタが未登録です。先にカテゴリを登録すると選べます。</p>
        @endif
    </div>

    <div>
        <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">発注業者</label>
        <select id="supplier_id" name="supplier_id"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <option value="">（未設定）</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ (string) old('supplier_id', $material->supplier_id) === (string) $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        @if ($suppliers->isEmpty())
            <p class="text-xs text-amber-600 mt-1">業者マスタが未登録です。先に業者を登録すると選べます。</p>
        @endif
    </div>

    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1">寸法（mm）</span>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label for="length_mm" class="block text-xs text-gray-500 mb-1">縦</label>
                <input autocomplete="off" id="length_mm" name="length_mm" type="number" min="0" value="{{ old('length_mm', $material->length_mm) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            </div>
            <div>
                <label for="width_mm" class="block text-xs text-gray-500 mb-1">横</label>
                <input autocomplete="off" id="width_mm" name="width_mm" type="number" min="0" value="{{ old('width_mm', $material->width_mm) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            </div>
            <div>
                <label for="height_mm" class="block text-xs text-gray-500 mb-1">高</label>
                <input autocomplete="off" id="height_mm" name="height_mm" type="number" min="0" value="{{ old('height_mm', $material->height_mm) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            </div>
        </div>
    </div>

    <div>
        <label for="size_text" class="block text-sm font-medium text-gray-700 mb-1">サイズ（自由入力）</label>
        <input autocomplete="off" id="size_text" name="size_text" type="text" value="{{ old('size_text', $material->size_text) }}"
               placeholder="例：粒外袋 W200×H300"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">規格名つきの表記など、縦横高とは別に自由に書けます。</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">単位 <span class="text-red-500">*</span></label>
            <select id="unit_id" name="unit_id" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                <option value="">（選択してください）</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" {{ (string) old('unit_id', $material->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @if ($units->isEmpty())
                <p class="text-xs text-amber-600 mt-1">単位マスタが未登録です。先に単位を登録すると選べます。</p>
            @endif
        </div>
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">単価（円）</label>
            <input autocomplete="off" id="unit_price" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price', $material->unit_price) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <p class="text-xs text-gray-400 mt-1">小数可（例：34.5）</p>
        </div>
    </div>

    <div>
        <label for="min_lot_qty" class="block text-sm font-medium text-gray-700 mb-1">最低ロット数量</label>
        <input autocomplete="off" id="min_lot_qty" name="min_lot_qty" type="number" min="0" value="{{ old('min_lot_qty', $material->min_lot_qty) }}"
               class="w-40 rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">単位は上の「単位」を使います（この数量以上でのみ発注可。端数はOK）。空欄ならロット制限なし。</p>
    </div>

    <div>
        <label for="note" class="block text-sm font-medium text-gray-700 mb-1">備考</label>
        <textarea id="note" name="note" rows="2"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">{{ old('note', $material->note) }}</textarea>
    </div>

    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1">画像</span>
        @if ($material->imageUrl())
            <div class="flex items-start gap-4 mb-2">
                <img src="{{ $material->imageUrl() }}" alt="{{ $material->name }}" data-zoom
                     class="w-24 h-24 object-cover rounded-md border border-gray-200 cursor-zoom-in transition hover:opacity-80">
                @include('admin.partials.toggle', [
                    'name' => 'remove_image',
                    'label' => '画像を削除する',
                    'checked' => false,
                ])
            </div>
        @endif
        {{-- ドラッグ＆ドロップ用の枠。CSV取り込みと同じ仕組み（layouts/app.blade.php の共通スクリプト）。
             label で input を包んでいるので、JSが動かなくてもクリックでファイル選択ダイアログが開く --}}
        <label data-dropzone data-dropzone-reject="JPEG / PNG / WebP の画像を落としてください。"
               class="dropzone flex flex-col items-center justify-center gap-1 w-full px-6 py-6
                      border-2 border-dashed border-gray-300 rounded-lg cursor-pointer text-center
                      hover:border-accent-dark hover:bg-accent-light/30 transition-colors">
            <input autocomplete="off" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
            {{-- 落とした画像はその場で出す（何を入れたか目で確かめられるように） --}}
            <img data-dropzone-thumb src="" alt=""
                 class="hidden w-24 h-24 object-cover rounded-md border border-gray-200">
            <span data-dropzone-icon class="text-2xl" aria-hidden="true">🖼️</span>
            <span data-dropzone-label class="text-sm text-gray-600">
                画像をここにドラッグ＆ドロップ
            </span>
            <span class="text-xs text-gray-400">クリックしてファイルを選ぶこともできます</span>
        </label>
        <p class="text-xs text-gray-400 mt-1">JPEG / PNG / WebP、5MBまで。{{ $material->imageUrl() ? '選ぶと差し替わります。' : '' }}</p>
    </div>

    @include('admin.partials.toggle', [
        'name' => 'has_imprint',
        'label' => '名入れあり',
        'checked' => old('has_imprint', $material->has_imprint ?? false),
    ])

    @include('admin.partials.toggle', [
        'name' => 'is_active',
        'label' => '有効にする（発注可能にする）',
        'checked' => old('is_active', $material->is_active ?? true),
    ])

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        {{-- 直前の絞り込み結果に戻す（素の一覧に戻すと検索条件が消えるため。$backUrl は MaterialController） --}}
        <a href="{{ $backUrl }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
