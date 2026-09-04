@php
    // トグルは未チェックだと送られてこないので、old() の有無ではなく
    // 「フォームを送ったあとか」で初期値を決める（送信後は入っているキーだけがオン）
    $visibleMasters = old('_submitted')
        ? array_keys((array) old('visible_masters', []))
        : ($user->visible_masters ?? []);
@endphp

<div class="space-y-4">
    <input type="hidden" name="_submitted" value="1">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">氏名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div>
        <label for="login_id" class="block text-sm font-medium text-gray-700 mb-1">ログインID <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="login_id" name="login_id" type="text" value="{{ old('login_id', $user->login_id) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">ログインに使うIDです。例：1st（営業所の申請用）, ooizumi（個人）</p>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
        <input autocomplete="off" id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">
            通知メールの宛先です。任意。営業所で共通のアカウントなど、宛先が無い場合は空のままで構いません（そのユーザーには通知が送られません）。
        </p>
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">権限 <span class="text-red-500">*</span></label>
        <select id="role" name="role" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            @foreach (\App\Models\User::ROLE_LABELS as $roleValue => $roleLabel)
                <option value="{{ $roleValue }}" {{ old('role', $user->role) === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
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

    @include('admin.partials.toggle', [
        'name' => 'is_manager',
        'label' => 'この営業所の<span class="font-medium">所長</span>にする（自営業所の発注申請を一次承認できる）',
        'checked' => old('is_manager', $user->is_manager ?? false),
    ])

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

    @include('admin.partials.toggle', [
        'name' => 'must_change_password',
        'label' => '次回ログイン時に<span class="font-medium">パスワードの変更を求める</span>（変えるまで他の画面を開けない）',
        'checked' => old('must_change_password', $isNew ? true : ($user->must_change_password ?? false)),
    ])
    <p class="text-xs text-gray-400 -mt-2">
        管理者が決めたパスワードのまま使わせないための設定です。
        営業所で共通のアカウントなど、本人に変えさせたくない場合はオフにしてください。
    </p>

    {{-- 表示するマスタ。管理者・総務は常に全部（編集もできる）なので、
         実際に効くのはそれ以外の権限＝オンにしたマスタを「閲覧だけ」できる --}}
    <div id="master-toggles" class="rounded-md border border-gray-200 p-4 space-y-3">
        <p class="text-sm font-medium text-gray-700">表示するマスタ</p>

        <p class="text-xs text-gray-400" data-master-note="all">
            管理者・総務は<span class="font-medium">すべてのマスタ</span>を編集できます。ここでは外せません。
        </p>
        <p class="text-xs text-gray-400 hidden" data-master-note="pick">
            オンにしたマスタを<span class="font-medium">閲覧だけ</span>できます（登録・編集・削除・CSVはできません）。
        </p>

        @foreach (\App\Models\User::MASTERS as $masterKey => $masterLabel)
            @include('admin.partials.toggle', [
                'name' => "visible_masters[{$masterKey}]",
                'label' => $masterLabel . 'マスタ',
                'checked' => in_array($masterKey, $visibleMasters, true),
                'between' => true,
            ])
        @endforeach
    </div>

    @include('admin.partials.toggle', [
        'name' => 'is_active',
        'label' => '有効にする（ログイン可能にする）',
        'checked' => old('is_active', $user->is_active ?? true),
    ])

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>

<script>
    // 管理者・総務は全マスタを扱えるので、トグルは常にオンで操作させない。
    // それ以外の権限のときだけ、1つずつ選ばせる（閲覧のみ）
    (function () {
        const FULL_ACCESS_ROLES = @json([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_GENERAL_AFFAIRS]);
        const role = document.getElementById('role');
        const box = document.getElementById('master-toggles');
        if (! role || ! box) return;

        const toggles = box.querySelectorAll('input[type="checkbox"]');
        const notes = {
            all: box.querySelector('[data-master-note="all"]'),
            pick: box.querySelector('[data-master-note="pick"]'),
        };
        const saved = new Map(); // 権限を戻したときに選択を復元する

        const sync = () => {
            const full = FULL_ACCESS_ROLES.includes(role.value);
            toggles.forEach((toggle) => {
                if (full) {
                    if (! saved.has(toggle.name)) saved.set(toggle.name, toggle.checked);
                    toggle.checked = true;
                } else if (saved.has(toggle.name)) {
                    toggle.checked = saved.get(toggle.name);
                    saved.delete(toggle.name);
                }
                toggle.disabled = full;
            });
            notes.all.classList.toggle('hidden', ! full);
            notes.pick.classList.toggle('hidden', full);
        };

        role.addEventListener('change', sync);
        sync();
    })();

    // パスワードを入力したら「次回ログイン時にパスワードの変更を求める」を自動でオンにする。
    // 管理者が決めたパスワードのまま使われるのを防ぐため。手で外せば外したままになる。
    (function () {
        const password = document.getElementById('password');
        const force = document.querySelector('input[name="must_change_password"]');
        if (! password || ! force) return;

        let touched = false;
        force.addEventListener('change', () => { touched = true; });
        password.addEventListener('input', () => {
            if (! touched && password.value !== '') force.checked = true;
        });
    })();
</script>
