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
        document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
            form.addEventListener('change', () => {
                form.setAttribute('aria-busy', 'true');
                form.submit();
            });
        });

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
