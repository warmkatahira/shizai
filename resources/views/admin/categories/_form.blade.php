<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">例：段ボール箱, 緩衝材, テープ・フィルム</p>
    </div>

    <div>
        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">表示順</label>
        <input autocomplete="off" id="sort_order" name="sort_order" type="number" min="0" max="9999"
               value="{{ old('sort_order', $category->sort_order ?? 0) }}"
               class="w-32 rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">小さい順に表示されます。</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
        有効にする
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
