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
        {{-- ロゴのマークはヘッダーと同じもの（public/favicon.svg）を使う --}}
        <div class="flex flex-col items-center gap-3 mb-7">
            <img src="/favicon.svg" alt="" aria-hidden="true"
                 class="w-14 h-14 rounded-2xl ring-1 ring-ink/5 shadow-sm">
            <div class="text-center">
                <h1 class="text-xl font-bold tracking-tight text-ink">資材発注システム</h1>
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

                <button type="submit"
                    class="w-full bg-accent hover:bg-accent-dark text-ink font-medium py-2.5 rounded-lg transition">
                    ログイン
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">{{ config('company.name') }}</p>
    </div>
</body>
</html>
