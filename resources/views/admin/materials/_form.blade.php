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
        <p class="text-xs text-gray-400 mt-1">単位は上の「単位」を使います（この数量の倍数でのみ発注可）。空欄ならロット制限なし。</p>
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
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300">
                    画像を削除する
                </label>
            </div>
        @endif
        <input autocomplete="off" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-accent file:px-4 file:py-2 file:text-ink hover:file:bg-accent-dark">
        <p class="text-xs text-gray-400 mt-1">JPEG / PNG / WebP、5MBまで。{{ $material->imageUrl() ? '選ぶと差し替わります。' : '' }}</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="has_imprint" value="1" class="rounded border-gray-300"
               {{ old('has_imprint', $material->has_imprint ?? false) ? 'checked' : '' }}>
        名入れあり
    </label>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $material->is_active ?? true) ? 'checked' : '' }}>
        有効にする（発注可能にする）
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.materials.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
