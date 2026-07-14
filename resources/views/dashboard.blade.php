@extends('layouts.app')

@section('title', 'ホーム | 資材発注システム')

@section('content')
    @php($user = auth()->user())

    <h1 class="text-xl font-bold mb-2">こんにちは、{{ $user->name }} さん</h1>
    <p class="text-gray-600 mb-6">
        あなたの権限は「<span class="font-medium">{{ $user->roleLabel() }}</span>」です。
        @if ($user->office)
            （所属: {{ $user->office->name }}）
        @endif
    </p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- 営業所ユーザー向け --}}
        @if ($user->isSales())
            <a href="{{ route('orders.create') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">資材を発注する</h2>
                <p class="text-sm text-gray-500">必要な資材を選んで発注申請します。</p>
            </a>
            <a href="{{ route('orders.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">申請履歴を見る</h2>
                <p class="text-sm text-gray-500">自分の営業所の発注申請と状態を確認します。</p>
            </a>
        @endif

        {{-- 総務向け --}}
        @if ($user->isGeneralAffairs())
            <a href="{{ route('orders.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">発注申請を確認する</h2>
                <p class="text-sm text-gray-500">営業所からの申請を確認します。<span class="text-xs text-gray-400">（承認・却下は Phase 4）</span></p>
            </a>
        @endif

        {{-- 管理者向け --}}
        @if ($user->isAdmin())
            <a href="{{ route('admin.offices.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">営業所管理</h2>
                <p class="text-sm text-gray-500">営業所を登録・編集します。</p>
            </a>
            <a href="{{ route('admin.users.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">ユーザー管理</h2>
                <p class="text-sm text-gray-500">利用者を登録・編集します。</p>
            </a>
            <a href="{{ route('admin.suppliers.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">業者マスタ管理</h2>
                <p class="text-sm text-gray-500">資材の仕入先業者を登録・編集します。</p>
            </a>
            <a href="{{ route('admin.materials.index') }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">資材マスタ管理</h2>
                <p class="text-sm text-gray-500">発注できる資材を登録・編集します。</p>
            </a>
        @endif
    </div>
@endsection
