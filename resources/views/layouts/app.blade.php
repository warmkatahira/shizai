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
        {{-- ヘッダー --}}
        <header class="bg-white shadow-sm">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="font-bold text-lg text-accent-strong">
                        資材発注システム
                    </a>
                    <nav class="hidden sm:flex items-center gap-4 text-sm text-gray-600">
                        <a href="{{ route('orders.index') }}" class="hover:text-accent-strong">発注申請</a>
                        <a href="{{ route('reports.index') }}" class="hover:text-accent-strong">集計</a>
                        @unless (auth()->user()->isAdmin())
                            {{-- 管理者はマスタ管理の「資材」から見られるので、こちらは出さない --}}
                            <a href="{{ route('materials.index') }}" class="hover:text-accent-strong">資材一覧</a>
                        @endunless
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.offices.index') }}" class="hover:text-accent-strong">営業所</a>
                            <a href="{{ route('admin.users.index') }}" class="hover:text-accent-strong">ユーザー</a>
                            <a href="{{ route('admin.suppliers.index') }}" class="hover:text-accent-strong">業者</a>
                            <a href="{{ route('admin.categories.index') }}" class="hover:text-accent-strong">カテゴリ</a>
                            <a href="{{ route('admin.materials.index') }}" class="hover:text-accent-strong">資材</a>
                        @endif
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-red-600">ログアウト</button>
                    </form>
                </div>
            </div>
        </header>

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
</body>
</html>
