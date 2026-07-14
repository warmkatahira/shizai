<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">氏名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">権限 <span class="text-red-500">*</span></label>
        <select id="role" name="role" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="office_id" class="block text-sm font-medium text-gray-700 mb-1">所属営業所</label>
        <select id="office_id" name="office_id"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <option value="">（なし）</option>
            @foreach ($offices as $office)
                <option value="{{ $office->id }}" {{ (string) old('office_id', $user->office_id) === (string) $office->id ? 'selected' : '' }}>
                    {{ $office->name }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">「営業所」権限の場合は必須です。</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_manager" value="1" class="rounded border-gray-300"
               {{ old('is_manager', $user->is_manager ?? false) ? 'checked' : '' }}>
        この営業所の<span class="font-medium">所長</span>にする（自営業所の発注申請を一次承認できる）
    </label>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            パスワード @if ($isNew)<span class="text-red-500">*</span>@endif
        </label>
        <input autocomplete="off" id="password" name="password" type="password" {{ $isNew ? 'required' : '' }}
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">
            8文字以上。@if (! $isNew)変更しない場合は空欄のままにしてください。@endif
        </p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input autocomplete="off" type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
               {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
        有効にする（ログイン可能にする）
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
