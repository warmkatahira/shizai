@php
    // 詳細確認リストでよく使われている発注方法。入力補助として候補に出す（自由入力も可）。
    $orderMethods = ['メール', 'FAX', '発注書FAX', 'サイボウズ', 'ロジレス', '電話', 'ネット', '専用サイト'];
@endphp

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

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">担当者名</label>
            <input autocomplete="off" id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $material->contact_person) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">連絡先</label>
            <input autocomplete="off" id="contact" name="contact" type="text" value="{{ old('contact', $material->contact) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="order_method" class="block text-sm font-medium text-gray-700 mb-1">発注方法</label>
            <input autocomplete="off" id="order_method" name="order_method" type="text" list="order-methods"
                   value="{{ old('order_method', $material->order_method) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <datalist id="order-methods">
                @foreach ($orderMethods as $method)
                    <option value="{{ $method }}"></option>
                @endforeach
            </datalist>
        </div>
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
            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">単位 <span class="text-red-500">*</span></label>
            <input autocomplete="off" id="unit" name="unit" type="text" value="{{ old('unit', $material->unit ?? '個') }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">単価（円）</label>
            <input autocomplete="off" id="unit_price" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price', $material->unit_price) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <p class="text-xs text-gray-400 mt-1">小数可（例：34.5）</p>
        </div>
    </div>

    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1">最低ロット</span>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="min_lot_qty" class="block text-xs text-gray-500 mb-1">数量</label>
                <input autocomplete="off" id="min_lot_qty" name="min_lot_qty" type="number" min="0" value="{{ old('min_lot_qty', $material->min_lot_qty) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            </div>
            <div>
                <label for="min_lot_unit" class="block text-xs text-gray-500 mb-1">単位</label>
                <input autocomplete="off" id="min_lot_unit" name="min_lot_unit" type="text" list="lot-units"
                       value="{{ old('min_lot_unit', $material->min_lot_unit) }}" placeholder="枚 / ケース / 本"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                <datalist id="lot-units">
                    <option value="枚"></option>
                    <option value="ケース"></option>
                    <option value="本"></option>
                    <option value="個"></option>
                    <option value="巻"></option>
                </datalist>
            </div>
        </div>
    </div>

    <div>
        <label for="note" class="block text-sm font-medium text-gray-700 mb-1">備考</label>
        <textarea id="note" name="note" rows="2"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">{{ old('note', $material->note) }}</textarea>
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
