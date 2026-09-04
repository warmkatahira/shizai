@php
    $user = auth()->user();

    // 現在いるページのリンクを目立たせる（どこにいるか分かるように）
    $current = [
        'orders' => request()->routeIs('orders.*'),
        'reports' => request()->routeIs('reports.*'),
        'catalog' => request()->routeIs('materials.*'),
        // 操作ログは独立タブなので、マスタ管理の現在地判定からは除く
        'masters' => request()->routeIs('admin.*') && ! request()->routeIs('admin.logs.*'),
        'logs' => request()->routeIs('admin.logs.*'),
    ];

    // ナビのリンクの見た目。現在地はベージュの塗り、それ以外はホバーでうっすら反応する
    $pill = fn (bool $active) => $active
        ? 'bg-accent-light text-accent-strong font-medium'
        : 'text-gray-500 hover:bg-gray-100 hover:text-ink';

    // 発注メニュー。申請・一覧・集計は全部「発注まわり」なので1つのドロップダウンにまとめる
    // match/except は現在地の判定用（orders.create は orders.* に含まれるので一覧側から除外する）
    $orders = collect([
        ['label' => '新規申請', 'route' => 'orders.create', 'match' => ['orders.create'], 'salesOnly' => true],
        ['label' => '発注申請一覧', 'route' => 'orders.index', 'match' => ['orders.*'], 'except' => ['orders.create']],
        ['label' => '発注集計', 'route' => 'reports.index', 'match' => ['reports.*'], 'backOfficeOnly' => true],
    ])->reject(fn ($item) => (($item['salesOnly'] ?? false) && ! $user->isSales())
        || (($item['backOfficeOnly'] ?? false) && ! $user->isBackOffice()));

    // ドロップダウンの各項目が現在地かどうか
    $isCurrent = fn (array $item) => request()->routeIs(...$item['match'])
        && (empty($item['except']) || ! request()->routeIs(...$item['except']));

    // マスタのメニュー。管理者・総務は全部、それ以外はユーザー管理でオンにしたものだけ（閲覧のみ）。
    // ユーザー管理だけは権限を付与できるので管理者のみ（トグルの対象にしていない）
    $masters = collect([
        ['label' => '資材', 'route' => 'admin.materials.index', 'master' => 'materials'],
        ['label' => 'カテゴリ', 'route' => 'admin.categories.index', 'master' => 'categories'],
        ['label' => '単位', 'route' => 'admin.units.index', 'master' => 'units'],
        ['label' => '業者', 'route' => 'admin.suppliers.index', 'master' => 'suppliers'],
        ['label' => '営業所', 'route' => 'admin.offices.index', 'master' => 'offices'],
        ['label' => 'ユーザー', 'route' => 'admin.users.index', 'adminOnly' => true],
    ])->reject(fn ($item) => isset($item['master'])
        ? ! $user->canViewMaster($item['master'])
        : (($item['adminOnly'] ?? false) && ! $user->isAdmin()));

    // 編集できない人には「管理」と言わない（一覧を見るだけなので）
    $mastersLabel = $user->canManageMasters() ? 'マスタ管理' : 'マスタ';
@endphp

{{-- 半透明＋ぼかしで、スクロールしても本文がうっすら透けて見える --}}
<header class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/85 backdrop-blur-md">
    <div class="relative max-w-6xl mx-auto px-4 h-16 flex items-center gap-3">

        {{-- ロゴ。マークはファビコン（public/favicon.svg）と同じものを使う --}}
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 shrink-0">
            <img src="/favicon.svg" alt="" aria-hidden="true"
                 class="w-9 h-9 rounded-xl ring-1 ring-ink/5 shadow-sm transition group-hover:shadow-md group-hover:-translate-y-px">
            <span class="leading-tight">
                <span class="block font-bold tracking-tight text-ink">資材発注システム</span>
                <span class="block text-[10px] tracking-[0.18em] text-gray-400">WARM</span>
            </span>
        </a>

        {{-- ナビ（PC） --}}
        <nav class="hidden md:flex items-center gap-1 ml-3 text-sm">
            {{-- 発注まわり（新規申請・一覧・集計）はマスタ管理と同じくドロップダウンにまとめる --}}
            <details class="group relative" data-menu>
                <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer select-none
                                flex items-center gap-1 px-3 py-1.5 rounded-full transition {{ $pill($current['orders'] || $current['reports']) }}">
                    発注
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 group-open:rotate-180"
                         viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.2 7.5a.75.75 0 0 1 1.06 0L10 11.2l3.74-3.7a.75.75 0 1 1 1.06 1.06l-4.27 4.24a.75.75 0 0 1-1.06 0L5.2 8.56a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                    </svg>
                </summary>
                <div class="absolute left-0 mt-2 w-44 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg shadow-ink/5">
                    @foreach ($orders as $item)
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-lg px-3 py-2 transition
                                  {{ $isCurrent($item)
                                      ? 'bg-accent-light text-accent-strong font-medium'
                                      : 'text-gray-600 hover:bg-gray-50 hover:text-ink' }}"
                           @if ($isCurrent($item)) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </details>

            @unless ($user->canManageMasters())
                {{-- マスタを編集できる人は「マスタ管理 > 資材」から見られるので、こちらは出さない --}}
                <a href="{{ route('materials.index') }}"
                   class="px-3 py-1.5 rounded-full transition {{ $pill($current['catalog']) }}"
                   @if ($current['catalog']) aria-current="page" @endif>資材一覧</a>
            @endunless

            @if ($masters->isNotEmpty())
                {{-- マスタは5つあってナビが渋滞するので、ドロップダウンにまとめる（JSフレームワークは使わず details で） --}}
                <details class="group relative" data-menu>
                    <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer select-none
                                    flex items-center gap-1 px-3 py-1.5 rounded-full transition {{ $pill($current['masters']) }}">
                        {{ $mastersLabel }}
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 group-open:rotate-180"
                             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.2 7.5a.75.75 0 0 1 1.06 0L10 11.2l3.74-3.7a.75.75 0 1 1 1.06 1.06l-4.27 4.24a.75.75 0 0 1-1.06 0L5.2 8.56a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </summary>
                    <div class="absolute left-0 mt-2 w-44 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg shadow-ink/5">
                        @foreach ($masters as $item)
                            <a href="{{ route($item['route']) }}"
                               class="block rounded-lg px-3 py-2 transition
                                      {{ request()->routeIs(Str::beforeLast($item['route'], '.') . '.*')
                                          ? 'bg-accent-light text-accent-strong font-medium'
                                          : 'text-gray-600 hover:bg-gray-50 hover:text-ink' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- 操作ログはマスタではないので、マスタ管理の右に独立タブとして出す（管理者のみ） --}}
            @if ($user->isAdmin())
                <a href="{{ route('admin.logs.index') }}"
                   class="px-3 py-1.5 rounded-full transition {{ $pill($current['logs']) }}"
                   @if ($current['logs']) aria-current="page" @endif>操作ログ</a>
            @endif
        </nav>

        {{-- ログイン中のユーザー --}}
        <div class="ml-auto flex items-center gap-2">
            {{-- 名前を押すとパスワード変更へ（他に本人が変えられる設定が無いので、専用ページは作らない） --}}
            <a href="{{ route('password.edit') }}" title="パスワードの変更"
               class="hidden sm:flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 py-1 pl-1 pr-3.5
                      transition hover:border-accent-dark hover:bg-accent-light/40
                      {{ request()->routeIs('password.*') ? 'border-accent-dark bg-accent-light' : '' }}">
                <span class="grid place-items-center w-7 h-7 rounded-full bg-accent-light text-accent-strong text-xs font-bold">
                    {{ mb_substr($user->name, 0, 1) }}
                </span>
                <span class="text-xs font-medium text-ink">{{ $user->name }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="ログアウト"
                        class="grid place-items-center w-9 h-9 rounded-full text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 17l5-5-5-5M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                    </svg>
                    <span class="sr-only">ログアウト</span>
                </button>
            </form>

            {{-- ハンバーガー（スマホ）。PCのナビは md 以上でしか出ないので、ここに全リンクを畳んでおく --}}
            <details class="group md:hidden" data-menu>
                <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer select-none
                                grid place-items-center w-9 h-9 rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-ink">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" aria-hidden="true">
                        <path class="group-open:hidden" d="M4 7h16M4 12h16M4 17h16"/>
                        <path class="hidden group-open:block" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                    <span class="sr-only">メニュー</span>
                </summary>

                <div class="absolute left-4 right-4 top-full mt-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-xl shadow-ink/5">
                    <p class="px-3 pt-1 pb-2 text-xs text-gray-400 sm:hidden">{{ $user->name }}</p>

                    <p class="px-3 pt-1 pb-1 text-[10px] tracking-wide text-gray-400">発注</p>
                    @foreach ($orders as $item)
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-lg px-3 py-2 text-sm
                                  {{ $isCurrent($item)
                                      ? 'bg-accent-light text-accent-strong font-medium'
                                      : 'text-gray-600 hover:bg-gray-50' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @unless ($user->canManageMasters())
                        <a href="{{ route('materials.index') }}"
                           class="block rounded-lg px-3 py-2 text-sm {{ $current['catalog'] ? 'bg-accent-light text-accent-strong font-medium' : 'text-gray-600 hover:bg-gray-50' }}">資材一覧</a>
                    @endunless

                    @if ($masters->isNotEmpty())
                        <p class="px-3 pt-3 pb-1 text-[10px] tracking-wide text-gray-400">{{ $mastersLabel }}</p>
                        @foreach ($masters as $item)
                            <a href="{{ route($item['route']) }}"
                               class="block rounded-lg px-3 py-2 text-sm
                                      {{ request()->routeIs(Str::beforeLast($item['route'], '.') . '.*')
                                          ? 'bg-accent-light text-accent-strong font-medium'
                                          : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    @endif

                    {{-- 操作ログ（管理者のみ。マスタとは別枠） --}}
                    @if ($user->isAdmin())
                        <a href="{{ route('admin.logs.index') }}"
                           class="block rounded-lg px-3 py-2 text-sm mt-1 {{ $current['logs'] ? 'bg-accent-light text-accent-strong font-medium' : 'text-gray-600 hover:bg-gray-50' }}">操作ログ</a>
                    @endif

                    {{-- パスワード変更（PCでは右上の名前から入る） --}}
                    <a href="{{ route('password.edit') }}"
                       class="block rounded-lg px-3 py-2 text-sm mt-1 border-t border-gray-100 pt-3
                              {{ request()->routeIs('password.*') ? 'bg-accent-light text-accent-strong font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                        パスワードの変更
                    </a>
                </div>
            </details>
        </div>
    </div>
</header>
