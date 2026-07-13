<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">品名 <span class="text-red-500">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $material->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
    </div>

    <div>
        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
        <input id="category" name="category" type="text" value="{{ old('category', $material->category) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
        <p class="text-xs text-gray-400 mt-1">任意。例：文具, 清掃用品 など</p>
    </div>

    <div>
        <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">仕入先業者</label>
        <select id="supplier_id" name="supplier_id"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
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

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">単位 <span class="text-red-500">*</span></label>
            <input id="unit" name="unit" type="text" value="{{ old('unit', $material->unit ?? '個') }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
        </div>
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">参考単価（円）</label>
            <input id="unit_price" name="unit_price" type="number" min="0" value="{{ old('unit_price', $material->unit_price) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $material->is_active ?? true) ? 'checked' : '' }}>
        有効にする（発注可能にする）
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.materials.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
