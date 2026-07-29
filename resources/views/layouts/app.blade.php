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
                {{-- フラッシュメッセージ（6秒後に自分で畳まれて消える。見た目は app.css の .flash-message）。
                     チェックマークは丸→レ点の順に線が引かれる（SVGの stroke-dashoffset） --}}
                @if (session('status'))
                    <div class="flash-message mb-4 flex items-center gap-2.5 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-green-800">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle class="check-circle" cx="12" cy="12" r="10" opacity="0.35"/>
                            <path class="check-mark" d="M7.2 12.4l3.3 3.3L16.9 9.3"/>
                        </svg>
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

        // フォーム送信で表示（ダウンロード系フォームは data-no-loader で除外）。
        // onsubmit="return confirm(...)" で「キャンセル」されたときは送信されないので出さない
        document.addEventListener('submit', (e) => {
            if (! e.defaultPrevented && ! e.target.hasAttribute('data-no-loader')) {
                showLoader();
            }
        });

        // 戻る/進む（bfcache 復元）で戻ったときはバーを消す
        window.addEventListener('pageshow', hideLoader);

        // 「入力したまま離れようとしたら確認」（発注申請フォーム）で離脱をキャンセルすると、
        // 先に出したローダーが残って操作できなくなる。ページが残っていたら消す。
        // 実際に離脱した場合はページごと消えるので、このタイマーは動かない。
        window.addEventListener('beforeunload', () => setTimeout(hideLoader, 0));

        // 一覧の行（<tr data-href>）は、どこを押しても詳細へ飛ぶ。
        // 行の中のリンク・ボタン・入力欄は本来の動作を優先し、文字を選択しただけのときも飛ばさない。
        // キーボード操作の人のために、行の中の「詳細」リンクはそのまま残してある。
        document.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-href]');
            if (! row || e.target.closest('a, button, input, label, select, textarea')) {
                return;
            }
            if (e.button !== 0 || e.defaultPrevented || window.getSelection().toString()) {
                return;
            }

            // Ctrl/⌘ クリックと中クリックは新しいタブで開く（リンクと同じ感覚で使えるように）
            if (e.ctrlKey || e.metaKey) {
                window.open(row.dataset.href, '_blank');
                return;
            }

            showLoader();
            window.location.href = row.dataset.href;
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

        // 「画面に入ったら動かす」演出。data-reveal を付けた要素が見えたときに is-in-view を付ける。
        // ページ下部にあるもの（ダッシュボードの推移グラフ）は、読み込み直後に動かすと
        // スクロールして見る頃には終わっているため。一度動かしたら監視をやめる。
        //
        // CSSの既定は「動き終わった状態」にしてあるので、JSが動かない環境や
        // 動きを抑える設定の人には、静止した状態でそのまま見える。
        if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && window.IntersectionObserver) {
            const revealTargets = document.querySelectorAll('[data-reveal]');

            if (revealTargets.length) {
                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in-view');
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.25 });

                revealTargets.forEach((el) => revealObserver.observe(el));
            }
        }

        // ファイルのドラッグ＆ドロップ（マスタのCSV取り込み・資材の画像）。
        // 枠は <label> が <input type="file"> を包んでいるので、クリックでの選択と
        // 「JSが動かないときは今までどおり」はHTMLだけで成立している。
        // ここでやるのは「落とされたファイルを input に入れる」ことと、選んだものの表示だけ。
        const dropzones = document.querySelectorAll('[data-dropzone]');

        // input の accept 属性に合うファイルかどうか。「.csv」も「image/*」も見る
        const isAccepted = (file, accept) => {
            if (! accept) {
                return true;
            }

            return accept.split(',').map((a) => a.trim().toLowerCase()).some((rule) => {
                if (rule.startsWith('.')) {
                    return file.name.toLowerCase().endsWith(rule);
                }
                if (rule.endsWith('/*')) {
                    return file.type.startsWith(rule.slice(0, -1));
                }

                return file.type.toLowerCase() === rule;
            });
        };

        dropzones.forEach((zone) => {
            const input = zone.querySelector('input[type="file"]');
            const label = zone.querySelector('[data-dropzone-label]');
            const thumb = zone.querySelector('[data-dropzone-thumb]');
            const icon = zone.querySelector('[data-dropzone-icon]');
            const defaultText = label.textContent.trim();

            const showPicked = () => {
                const file = input.files[0];
                label.textContent = file ? file.name : defaultText;
                zone.classList.toggle('is-filled', Boolean(file));

                // 画像の枠なら、選んだ絵をその場で出す（何を入れたか目で確かめられるように）
                if (thumb) {
                    if (file) {
                        // 前に作ったURLは解放する（開きっぱなしにしない）
                        if (thumb.dataset.objectUrl) {
                            URL.revokeObjectURL(thumb.dataset.objectUrl);
                        }
                        thumb.dataset.objectUrl = URL.createObjectURL(file);
                        thumb.src = thumb.dataset.objectUrl;
                    }
                    thumb.classList.toggle('hidden', ! file);
                    icon && icon.classList.toggle('hidden', Boolean(file));
                }
            };

            input.addEventListener('change', showPicked);

            ['dragenter', 'dragover'].forEach((type) => {
                zone.addEventListener(type, (e) => {
                    e.preventDefault();
                    zone.classList.add('is-dragover');
                });
            });

            zone.addEventListener('dragleave', (e) => {
                // 枠の中の要素をまたぐときにも dragleave が飛ぶので、本当に外へ出たときだけ戻す
                if (! zone.contains(e.relatedTarget)) {
                    zone.classList.remove('is-dragover');
                }
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('is-dragover');

                const file = e.dataTransfer.files[0];
                if (! file) {
                    return;
                }

                if (! isAccepted(file, input.accept)) {
                    label.textContent = zone.dataset.dropzoneReject || 'この形式のファイルは選べません。';

                    return;
                }

                // 複数落とされても1つだけ受け取る
                const picked = new DataTransfer();
                picked.items.add(file);
                input.files = picked.files;
                showPicked();
            });
        });

        // 枠を外して落としたときに、ブラウザがそのファイルを開いて画面が消えるのを防ぐ。
        // 取り込みパネルがあるページだけで効かせる
        if (dropzones.length) {
            ['dragover', 'drop'].forEach((type) => {
                document.addEventListener(type, (e) => {
                    if (! e.target.closest('[data-dropzone]')) {
                        e.preventDefault();
                    }
                });
            });
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
