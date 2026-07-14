<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">業者名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $supplier->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">業者コード</label>
        <input autocomplete="off" id="code" name="code" type="text" value="{{ old('code', $supplier->code) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">担当者名</label>
        <input autocomplete="off" id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $supplier->contact_person) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
            <input autocomplete="off" id="phone" name="phone" type="text" value="{{ old('phone', $supplier->phone) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="fax" class="block text-sm font-medium text-gray-700 mb-1">FAX番号</label>
            <input autocomplete="off" id="fax" name="fax" type="text" value="{{ old('fax', $supplier->fax) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
            <input autocomplete="off" id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
    </div>


    <div>
        <label for="order_method" class="block text-sm font-medium text-gray-700 mb-1">発注方法</label>
        <select id="order_method" name="order_method"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <option value="">（未設定）</option>
            @foreach (\App\Models\Supplier::ORDER_METHODS as $value => $label)
                <option value="{{ $value }}" {{ old('order_method', $supplier->order_method) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">この業者への発注をどの手段で行うか。サイボウズ・ロジレスなどの専用システムは「web」を選んでください。</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
        有効にする
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.suppliers.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
