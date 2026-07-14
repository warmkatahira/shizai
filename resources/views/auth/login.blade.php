<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン | 資材発注システム</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <h1 class="text-center text-2xl font-bold text-accent-strong mb-6">資材発注システム</h1>

        <div class="bg-white shadow rounded-lg p-6">
            {{-- エラー表示 --}}
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
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
                    <label for="login_id" class="block text-sm font-medium text-gray-700 mb-1">ログインID</label>
                    <input autocomplete="off" id="login_id" name="login_id" type="text" value="{{ old('login_id', $devLoginId) }}" required autofocus
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                    <input autocomplete="off" id="password" name="password" type="password" value="{{ old('password', $devPassword) }}" required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input autocomplete="off" type="checkbox" name="remember" class="rounded border-gray-300">
                    ログイン状態を保持する
                </label>

                <button type="submit"
                    class="w-full bg-accent hover:bg-accent-dark text-ink font-medium py-2 rounded-md transition">
                    ログイン
                </button>
            </form>
        </div>
    </div>
</body>
</html>
