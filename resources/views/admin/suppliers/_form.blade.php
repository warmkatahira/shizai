<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">業者名 <span class="text-red-500">*</span></label>
        <input autocomplete="off" id="name" name="name" type="text" value="{{ old('name', $supplier->name) }}" required
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">
            一覧・発注申請・集計に出る名前です。「株式会社」は付けず短めにしてください。
        </p>
    </div>

    <div>
        <label for="formal_name" class="block text-sm font-medium text-gray-700 mb-1">正式名称</label>
        <input autocomplete="off" id="formal_name" name="formal_name" type="text" value="{{ old('formal_name', $supplier->formal_name) }}"
               placeholder="例：株式会社フレックス"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        <p class="text-xs text-gray-400 mt-1">
            発注書の宛名（〜御中）だけに使います。空のままなら業者名がそのまま宛名になります。
        </p>
    </div>

    <div>
        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">担当者名</label>
        <input autocomplete="off" id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $supplier->contact_person) }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
    </div>

    <div class="grid grid-cols-3 gap-4">
        {{-- 電話は固定と携帯を別に持つ。発注書に出るのは固定電話（とFAX） --}}
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">固定電話</label>
            <input autocomplete="off" id="phone" name="phone" type="text" value="{{ old('phone', $supplier->phone) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="mobile_phone" class="block text-sm font-medium text-gray-700 mb-1">携帯電話</label>
            <input autocomplete="off" id="mobile_phone" name="mobile_phone" type="text" value="{{ old('mobile_phone', $supplier->mobile_phone) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="fax" class="block text-sm font-medium text-gray-700 mb-1">FAX番号</label>
            <input autocomplete="off" id="fax" name="fax" type="text" value="{{ old('fax', $supplier->fax) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
    </div>

    {{-- メールは2つまで持てる（担当者と事務所の両方に送りたい業者があるため）。どちらも任意 --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
            <input autocomplete="off" id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>
        <div>
            <label for="email2" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス2</label>
            <input autocomplete="off" id="email2" name="email2" type="email" value="{{ old('email2', $supplier->email2) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <p class="text-xs text-gray-400 mt-1">送り先が2つある業者だけ入力してください。</p>
        </div>
    </div>


    <div>
        <label for="order_method" class="block text-sm font-medium text-gray-700 mb-1">発注方法</label>
        <select id="order_method" name="order_method"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            <option value="">（未設定）</option>
            @foreach (\App\Models\Supplier::ORDER_METHODS as $methodValue => $methodLabel)
                <option value="{{ $methodValue }}" {{ old('order_method', $supplier->order_method) === $methodValue ? 'selected' : '' }}>{{ $methodLabel }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">この業者への発注をどの手段で行うか。サイボウズ・ロジレスなどの専用システムは「web」を選んでください。</p>
    </div>

    {{-- 発注方法が「web」のときだけ出す。発注サイトのURL・ログイン情報（すべて任意） --}}
    <div data-web-fields class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4">
        <p class="text-sm font-medium text-gray-700">発注サイト</p>

        <div>
            <label for="web_url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
            {{-- type="url" にすると、枠を隠しているとき（web以外）に不正な値が残っていると
                 ブラウザが保存を止めてしまう（隠れた項目にフォーカスできないため）。形式はサーバー側で見る --}}
            <input autocomplete="off" id="web_url" name="web_url" type="text" value="{{ old('web_url', $supplier->web_url) }}"
                   placeholder="https://..."
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="web_login_id" class="block text-sm font-medium text-gray-700 mb-1">ログインID</label>
                <input autocomplete="off" id="web_login_id" name="web_login_id" type="text" maxlength="100"
                       value="{{ old('web_login_id', $supplier->web_login_id) }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
            </div>
            <div>
                <label for="web_password" class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                <div class="flex items-center gap-2">
                    <input autocomplete="off" id="web_password" name="web_password" type="password" maxlength="100"
                           value="{{ old('web_password', $supplier->web_password) }}"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                    {{-- 入っている値を確かめられるように表示を切り替えられる（発注する人が見て入力するものなので） --}}
                    <button type="button" data-toggle-password="web_password"
                            class="shrink-0 text-xs text-accent-strong hover:underline">表示</button>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400">発注する人が見るための控えです。分かるものだけ入れてください（すべて空欄でも保存できます）。</p>
    </div>

    {{-- 発注方法が「web」のときだけ発注サイトの枠を出す。入力済みの値は消さない（選び直しても残る） --}}
    <script>
        (function () {
            const method = document.getElementById('order_method');
            const fields = document.querySelector('[data-web-fields]');
            const toggle = document.querySelector('[data-toggle-password]');
            const password = document.getElementById(toggle.dataset.togglePassword);

            function show() {
                fields.hidden = method.value !== 'web';
            }

            toggle.addEventListener('click', function () {
                const hiddenNow = password.type === 'password';
                password.type = hiddenNow ? 'text' : 'password';
                toggle.textContent = hiddenNow ? '隠す' : '表示';
            });

            method.addEventListener('change', show);
            show();
        })();
    </script>

    @include('admin.partials.toggle', [
        'name' => 'is_active',
        'label' => '有効にする',
        'checked' => old('is_active', $supplier->is_active ?? true),
    ])

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">保存</button>
        <a href="{{ route('admin.suppliers.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
    </div>
</div>
