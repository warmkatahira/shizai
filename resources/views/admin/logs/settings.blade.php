@extends('layouts.app')

@section('title', '操作ログの記録設定')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">操作ログの記録設定</h1>
        <a href="{{ route('admin.logs.index') }}"
           class="text-sm text-accent-strong hover:underline">← 操作ログ一覧に戻る</a>
    </div>

    <p class="text-sm text-gray-500 mb-6">
        オンにした操作だけがログに記録されます。ここで切り替えるだけで、いつでもオン/オフを変更できます。
        <br>ダウンロード系は既定オフです（必要になったらオンにしてください）。
    </p>

    <form method="POST" action="{{ route('admin.logs.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach ($groups as $group)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-700">{{ $group['label'] }}</h2>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($group['actions'] as $a)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="text-sm text-ink">{{ $a['label'] }}</span>

                                {{-- iOS風トグル（オンで緑）。JSフレームワークは使わず peer で制御 --}}
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" name="actions[{{ $a['action'] }}]" value="1"
                                           class="sr-only peer" {{ $a['enabled'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 rounded-full bg-gray-300 transition-colors peer-checked:bg-[#34C759]
                                                peer-focus-visible:ring-2 peer-focus-visible:ring-[#34C759]/40"></div>
                                    <div class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow
                                                transition-transform peer-checked:translate-x-5"></div>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                    class="bg-accent hover:bg-accent-dark text-ink text-sm font-medium px-6 py-2.5 rounded-md">
                設定を保存
            </button>
            <a href="{{ route('admin.logs.index') }}"
               class="text-sm text-gray-500 hover:text-ink">キャンセル</a>
        </div>
    </form>
@endsection
