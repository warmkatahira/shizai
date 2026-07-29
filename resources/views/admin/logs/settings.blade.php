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
                            <li class="px-5 py-3">
                                @include('admin.partials.toggle', [
                                    'name' => "actions[{$a['action']}]",
                                    'label' => e($a['label']),
                                    'checked' => $a['enabled'],
                                    'between' => true,
                                ])
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
