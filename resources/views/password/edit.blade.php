@extends('layouts.app')

@section('title', 'パスワードの変更')

@section('content')
    <h1 class="text-xl font-bold mb-6">パスワードの変更</h1>

    <div class="bg-white shadow rounded-lg p-6 max-w-md">
        @include('admin.partials.errors')

        {{-- 管理者に変更を求められている状態。変えるまで他の画面は開けない（EnsurePasswordChanged） --}}
        @if (auth()->user()->must_change_password)
            <div class="mb-5 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <span class="font-medium">パスワードの変更が必要です。</span>
                管理者が設定したパスワードのままです。変更するまで他の画面は使えません。
            </div>
        @endif

        <p class="text-sm text-gray-500 mb-5">
            ログインID <span class="font-medium text-ink">{{ auth()->user()->login_id }}</span> のパスワードを変更します。
        </p>

        {{-- 営業所の申請用アカウントは拠点で使い回しているので、変えると他の人も入れなくなる --}}
        @if (auth()->user()->isSales() && ! auth()->user()->is_manager)
            <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                このアカウントは営業所で共通で使っています。変更すると、
                <span class="font-medium">同じアカウントを使っている全員がログインできなくなります</span>。
                変えたときは営業所内に必ず伝えてください。
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                        現在のパスワード <span class="text-red-500">*</span>
                    </label>
                    <input autocomplete="off" id="current_password" name="current_password" type="password" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                    @if (auth()->user()->must_change_password)
                        <p class="text-xs text-gray-400 mt-1">いまログインに使ったパスワード（管理者から伝えられたもの）です。</p>
                    @endif
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        新しいパスワード <span class="text-red-500">*</span>
                    </label>
                    <input autocomplete="off" id="password" name="password" type="password" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                    <p class="text-xs text-gray-400 mt-1">8文字以上。</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        新しいパスワード（確認） <span class="text-red-500">*</span>
                    </label>
                    <input autocomplete="off" id="password_confirmation" name="password_confirmation" type="password" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none">
                    <p class="text-xs text-gray-400 mt-1">打ち間違い防止のため、もう一度入れてください。</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">変更する</button>
                    {{-- 変更が必須のときは戻る先が無い（戻してもここへ戻される）ので出さない --}}
                    @unless (auth()->user()->must_change_password)
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
                    @endunless
                </div>
            </div>
        </form>
    </div>
@endsection
