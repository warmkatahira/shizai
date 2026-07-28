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
                {{-- フラッシュメッセージ（6秒後に自分で畳まれて消える。見た目は app.css の .flash-message） --}}
                @if (session('status'))
                    <div class="flash-message mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

    </div>

    {{-- 画像の拡大表示（ライトボックス）。data-zoom を付けた画像をクリックすると開く。
         背景クリック・✕・Esc で閉じる。JSフレームワークは使わない。 --}}
    <div id="image-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 cursor-zoom-out"
         role="dialog" aria-modal="true" aria-hidden="true">
        <img src="" alt="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
        <button type="button" data-lightbox-close aria-label="閉じる"
                class="absolute top-4 right-4 grid place-items-center w-10 h-10 rounded-full bg-white/90 text-ink text-lg hover:bg-white">✕</button>
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
        // すぐ切り替わるページでは暗幕を出さない（一瞬の点滅がうるさく、ページ遷移の
        // クロスフェードとも噛み合わないため）。250ms を超えて待たされたときだけ出す。
        const pageLoader = document.getElementById('page-loader');
        let loaderTimer = null;

        const showLoader = () => {
            if (! pageLoader || loaderTimer) return;
            loaderTimer = setTimeout(() => pageLoader.classList.add('is-active'), 250);
        };

        const hideLoader = () => {
            clearTimeout(loaderTimer);
            loaderTimer = null;
            pageLoader && pageLoader.classList.remove('is-active');
        };

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

        // 数字のカウントアップ。data-countup="1234" を付けた要素が 0 から回る。
        // 終わったら元のテキスト（¥や小数を含む整形済みの文字列）に戻すので、表示は必ず正確になる。
        const animateCountUp = (el) => {
            const target = parseFloat(el.dataset.countup);
            const finalText = el.textContent;

            if (! isFinite(target) || target <= 0) {
                return;
            }

            const prefix = el.dataset.countupPrefix || '';
            const duration = 600;
            const startedAt = performance.now();

            const step = (now) => {
                const progress = Math.min(1, (now - startedAt) / duration);
                // 最後にゆっくり止まる（ease-out）
                const eased = 1 - Math.pow(1 - progress, 3);

                if (progress < 1) {
                    el.textContent = prefix + Math.round(target * eased).toLocaleString();
                    requestAnimationFrame(step);
                } else {
                    el.textContent = finalText;
                }
            };

            requestAnimationFrame(step);
        };

        if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll('[data-countup]').forEach(animateCountUp);
        }

        // 画像の拡大表示（ライトボックス）
        const lightbox = document.getElementById('image-lightbox');
        const lightboxImg = lightbox ? lightbox.querySelector('img') : null;

        const openLightbox = (src, alt) => {
            if (! lightbox) return;
            lightboxImg.src = src;
            lightboxImg.alt = alt || '';
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
            lightbox.setAttribute('aria-hidden', 'false');
        };

        const closeLightbox = () => {
            if (! lightbox) return;
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImg.src = '';
        };

        document.addEventListener('click', (e) => {
            const zoom = e.target.closest('[data-zoom]');
            if (zoom) {
                e.preventDefault();
                openLightbox(zoom.getAttribute('data-zoom-src') || zoom.getAttribute('src'), zoom.getAttribute('alt'));
                return;
            }
            // 背景（オーバーレイ自身）か ✕ をクリックしたら閉じる
            if (e.target === lightbox || e.target.closest('[data-lightbox-close]')) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    </script>
</body>
</html>
