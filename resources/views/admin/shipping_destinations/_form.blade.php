<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">直送先名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $destination->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">発注申請のプルダウンに出る名前です。例：〇〇株式会社 △△倉庫</p>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <div>
            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">郵便番号</label>
            <input autocomplete="off" id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $destination->postal_code) }}" placeholder="340-0822"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div class="col-span-3">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">住所 <span class="text-red-500">*</span></label>
            <input autocomplete="off" id="address" name="address" type="text" value="{{ old('address', $destination->address) }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <p class="text-xs text-gray-400 mt-1">発注書の【納入先】欄にそのまま印字されます。</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label for="tel" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
            <input autocomplete="off" id="tel" name="tel" type="text" value="{{ old('tel', $destination->tel) }}" placeholder="048-995-0001"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="fax" class="block text-sm font-medium text-gray-700 mb-1">FAX番号</label>
            <input autocomplete="off" id="fax" name="fax" type="text" value="{{ old('fax', $destination->fax) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">表示順</label>
            <input autocomplete="off" id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $destination->sort_order ?? 0) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
    </div>

    @include('admin.partials.toggle', [
        'name' => 'is_active',
        'label' => '有効にする（発注申請のプルダウンに出す）',
        'checked' => old('is_active', $destination->is_active ?? true),
    ])

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.shipping_destinations.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
