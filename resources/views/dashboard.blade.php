@extends('layouts.app')

@section('title', 'ホーム | 資材発注システム')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    @if ($user->office)
        <p class="text-gray-600 mb-6">所属: {{ $user->office->name }}</p>
    @endif

    @php
        // 対応すべきこと（todo）のトーン別カラー
        $toneClasses = [
            'amber' => 'text-amber-700',
            'blue' => 'text-blue-700',
            'accent' => 'text-accent-strong',
            'orange' => 'text-orange-700',
        ];
        // グラフのスケール（最大金額。0除算を避ける）
        $trendMax = max(1, ...array_map(fn ($t) => $t['amount'], $trend));
    @endphp

    {{-- 対応が必要なもの（役割別）。件数が0のものも出すが、0のときは控えめに見せる --}}
    @if (! empty($todos))
        <h2 class="text-sm font-bold text-gray-500 mb-3">対応が必要なもの</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            @foreach ($todos as $todo)
                <a href="{{ route('orders.index', $todo['query']) }}"
                   class="flex items-center justify-between bg-white rounded-2xl shadow-sm px-5 py-4 transition hover:shadow-md hover:ring-2 hover:ring-accent">
                    <span class="text-sm text-gray-600">{{ $todo['label'] }}</span>
                    <span class="text-2xl font-bold {{ $todo['count'] > 0 ? $toneClasses[$todo['tone']] : 'text-gray-300' }}">
                        {{ number_format($todo['count']) }}<span class="text-sm font-normal ml-0.5">件</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- 当月KPI ＋ 月別の発注金額推移 --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        {{-- KPIタイル --}}
        <div class="grid grid-cols-3 gap-4 lg:col-span-1 lg:grid-cols-1">
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注件数</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ number_format($kpis['count']) }}<span class="text-sm font-normal ml-0.5">件</span></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注金額</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ \App\Support\Money::yen($kpis['amount']) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の申請件数</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ number_format($kpis['applied']) }}<span class="text-sm font-normal ml-0.5">件</span></p>
            </div>
        </div>

        {{-- 月別の発注金額推移（発注日ベース・発注済のみ）。JSは使わずCSSの棒グラフで描く --}}
        <div class="bg-white rounded-2xl shadow-sm px-5 py-4 lg:col-span-2">
            <p class="text-xs text-gray-500 mb-4">発注金額の推移（直近6ヶ月・発注済）</p>
            <div class="flex items-end gap-3 h-40">
                @foreach ($trend as $t)
                    <div class="flex-1 flex flex-col items-center justify-end h-full"
                         title="{{ $t['label'] }}：{{ \App\Support\Money::yen($t['amount']) }} / {{ number_format($t['count']) }}件">
                        {{-- 0円の月も ¥0 と出す（空白だと「データが無い」のか「0円」なのか分からない） --}}
                        <span class="text-[10px] mb-1 {{ $t['amount'] > 0 ? 'text-gray-400' : 'text-gray-300' }}">
                            {{ \App\Support\Money::yen($t['amount']) }}
                        </span>
                        {{-- 当月は色を濃くして「まだ途中の月」だと分かるようにする --}}
                        <div class="w-full rounded-t {{ $t['is_current'] ? 'bg-accent-strong' : 'bg-accent' }}"
                             style="height: {{ max(2, round($t['amount'] / $trendMax * 100)) }}%"></div>
                        <span class="text-xs mt-2 {{ $t['is_current'] ? 'text-ink font-bold' : 'text-gray-500' }}">{{ $t['label'] }}</span>
                        {{-- 金額だけだと高額資材1件で跳ねるので件数も添える --}}
                        <span class="text-[10px] text-gray-400">{{ number_format($t['count']) }}件</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
