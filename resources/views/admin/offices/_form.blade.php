<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">営業所名 <span class="text-red-500">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $office->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
    </div>

    <div>
        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">営業所コード</label>
        <input id="code" name="code" type="text" value="{{ old('code', $office->code) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
        <p class="text-xs text-gray-400 mt-1">任意。例：TKY, OSK など</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $office->is_active ?? true) ? 'checked' : '' }}>
        有効にする
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.offices.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
