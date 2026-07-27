<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '資材発注システム')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">
    {{-- ページ遷移中のローディングバー（見た目は app.css の #page-loader） --}}
    <div id="page-loader" role="progressbar" aria-hidden="true"></div>

    <div class="min-h-screen flex flex-col">
        @include('layouts.partials.header')

        {{-- 本文 --}}
        <main class="flex-1">
            <div class="max-w-6xl mx-auto px-4 py-8">
                {{-- フラッシュメッセージ --}}
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="py-4 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} 資材発注システム
        </footer>
    </div>

    {{--
        data-auto-submit を付けた検索フォームは、条件を変えた時点で自動的に検索する。
        change イベントを使うので、テキスト入力は「Enter」か「フォーカスを外したとき」だけ発火し、
        1文字ごとにページが再読み込みされることはない。
        JSが動かない場合は、フォーム内の検索ボタンがそのまま使える。
    --}}
    <script>
        // ページ遷移中のローディングバー。リンク遷移・フォーム送信で表示し、
        // 次のページに切り替わるとDOMごと差し替わって自然に消える。
        // ファイルのダウンロード（CSV・発注書PDF）はページが変わらず消えないので、
        // data-no-loader を付けた要素からは出さない。
        const pageLoader = document.getElementById('page-loader');
        const showLoader = () => pageLoader && pageLoader.classList.add('is-active');
        const hideLoader = () => pageLoader && pageLoader.classList.remove('is-active');

        document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
            form.addEventListener('change', () => {
                form.setAttribute('aria-busy', 'true');
                showLoader();
                form.submit();
            });
        });

        // 通常のリンククリックで表示（別タブ・ダウンロード・ページ内リンク等は除く）
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (! link || link.closest('[data-no-loader]') || link.hasAttribute('data-no-loader')) {
                return;
            }
            const href = link.getAttribute('href');
            if (! href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
                return;
            }
            // 別タブ・修飾キー・右クリック・ダウンロード・別オリジンは対象外
            if (link.target === '_blank' || link.hasAttribute('download') || link.origin !== window.location.origin) {
                return;
            }
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }
            showLoader();
        });

        // フォーム送信で表示（ダウンロード系フォームは data-no-loader で除外）
        document.addEventListener('submit', (e) => {
            if (! e.target.hasAttribute('data-no-loader')) {
                showLoader();
            }
        });

        // 戻る/進む（bfcache 復元）で戻ったときはバーを消す
        window.addEventListener('pageshow', hideLoader);

        // ヘッダーのメニュー（<details data-menu>）は、外側をクリックするか Esc で閉じる。
        // details のままだと開きっぱなしになり、他のメニューと重なって見えるため。
        const menus = document.querySelectorAll('details[data-menu]');

        document.addEventListener('click', (e) => {
            menus.forEach((menu) => {
                if (menu.open && ! menu.contains(e.target)) {
                    menu.open = false;
                }
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                menus.forEach((menu) => (menu.open = false));
            }
        });

        // メニューを開いたら、他の開いているメニューは閉じる
        menus.forEach((menu) => {
            menu.addEventListener('toggle', () => {
                if (! menu.open) {
                    return;
                }

                menus.forEach((other) => {
                    if (other !== menu) {
                        other.open = false;
                    }
                });
            });
        });
    </script>
</body>
</html>
