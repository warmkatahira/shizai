<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">営業所名 <span class="text-red-500">*</span></label>
            <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $office->name) }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">営業所コード</label>
            <input autocomplete="off" id="code" name="code" type="text" value="{{ old('code', $office->code) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <p class="text-xs text-gray-400 mt-1">任意。例：1st, LS, IMP など</p>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <div>
            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">郵便番号</label>
            <input autocomplete="off" id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $office->postal_code) }}" placeholder="340-0822"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div class="col-span-3">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">住所</label>
            <input autocomplete="off" id="address" name="address" type="text" value="{{ old('address', $office->address) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label for="tel" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
            <input autocomplete="off" id="tel" name="tel" type="text" value="{{ old('tel', $office->tel) }}" placeholder="048-995-0001"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="fax" class="block text-sm font-medium text-gray-700 mb-1">FAX番号</label>
            <input autocomplete="off" id="fax" name="fax" type="text" value="{{ old('fax', $office->fax) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">表示順</label>
            <input autocomplete="off" id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $office->sort_order ?? 0) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $office->is_active ?? true) ? 'checked' : '' }}>
        有効にする
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.offices.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
