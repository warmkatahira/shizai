<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン | 資材発注システム</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- 背景は上からアクセント色がごく薄く落ちるグラデーション（見た目は app.css の .auth-bg） --}}
<body class="auth-bg min-h-screen flex items-center justify-center px-4 py-10 text-gray-800">
    <div class="stagger w-full max-w-sm">
        {{-- ロゴ。ここだけは中の線を動かすので <img> ではなくSVGを直接置く（partials/logo-mark） --}}
        <div class="flex flex-col items-center gap-3 mb-7">
            @include('partials.logo-mark', [
                'animate' => true,
                'class' => 'w-14 h-14 rounded-2xl ring-1 ring-ink/5 shadow-sm',
            ])
            <div class="text-center">
                {{-- 見出しは輪郭をなぞってから塗る（手書き風）。
                     文字はSVGで描くので、読み上げ用に本物の見出しを隠して置いておく。
                     viewBox は 208×32 で w-52 h-8（＝1:1）にしてあるので、単位はそのままpxとして効く --}}
                <h1 class="sr-only">資材発注システム</h1>
                <svg viewBox="0 0 208 32" class="w-52 h-8 mx-auto" aria-hidden="true" focusable="false">
                    <text class="title-write" x="104" y="24" text-anchor="middle" font-size="22">資材発注システム</text>
                </svg>
                <p class="text-[10px] tracking-[0.18em] text-gray-400 mt-0.5">WARM</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-ink/5 shadow-sm p-7">
            {{-- エラー表示 --}}
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                // 開発中のログインを省くための初期値。本番（local 以外）では空になる。
                $devLoginId = app()->isLocal() ? 't.katahira' : '';
                $devPassword = app()->isLocal() ? 'katahira134' : '';
            @endphp

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="login_id" class="block text-xs font-medium tracking-wide text-gray-500 mb-1.5">ログインID</label>
                    <input autocomplete="off" id="login_id" name="login_id" type="text" value="{{ old('login_id', $devLoginId) }}" required autofocus
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-accent-dark focus:ring-2 focus:ring-accent-dark/40 outline-none transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium tracking-wide text-gray-500 mb-1.5">パスワード</label>
                    <input autocomplete="off" id="password" name="password" type="password" value="{{ old('password', $devPassword) }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-accent-dark focus:ring-2 focus:ring-accent-dark/40 outline-none transition">
                </div>

                {{-- 送信するとボタンの中でリングが回り、押した手応えを返す --}}
                <button type="submit" data-login-button
                    class="w-full flex items-center justify-center gap-2 bg-accent hover:bg-accent-dark text-ink font-medium py-2.5 rounded-lg transition disabled:opacity-80">
                    <svg data-login-spinner class="hidden w-4 h-4 animate-spin motion-reduce:animate-none"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" opacity="0.3"/>
                        <path d="M21 12a9 9 0 0 0-9-9" stroke-linecap="round"/>
                    </svg>
                    <span data-login-label>ログイン</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">{{ config('company.name') }}</p>
    </div>

    <script>
        // 送信中はボタンをスピナー表示にして、二重送信も防ぐ。
        // （ログイン画面は layouts/app を使わない独立ページなので、共通のローダーは効かない）
        document.querySelector('form').addEventListener('submit', (e) => {
            if (e.defaultPrevented) {
                return;
            }

            document.querySelector('[data-login-spinner]').classList.remove('hidden');
            document.querySelector('[data-login-label]').textContent = 'ログイン中…';
            document.querySelector('[data-login-button]').disabled = true;
        });
    </script>
</body>
</html>
