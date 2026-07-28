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
        // グラフのスケール（最大金額。0除算を避ける）
        $trendMax = max(1, ...array_map(fn ($t) => $t['amount'], $trend));
    @endphp

    {{-- 対応が必要なもの（役割別）。件数が0のものも出すが、0のときは控えめに見せる。
         タイルは全部同じ大きさ（grid-cols-2 に揃え、中身の量で高さが変わらないよう h-full） --}}
    @if (! empty($todos))
        <h2 class="text-sm font-bold text-gray-500 mb-3">対応が必要なもの</h2>
        <div class="stagger grid gap-4 sm:grid-cols-2 mb-10">
            @foreach ($todos as $todo)
                {{-- 残っているものだけ、読み込み直後にリングを3回だけ広げて気づかせる（pulse-ring） --}}
                <a href="{{ route('orders.index', $todo['query']) }}"
                   class="group h-full flex flex-col justify-between bg-white rounded-2xl ring-1 ring-ink/5 px-6 py-5
                          transition hover:-translate-y-px hover:ring-accent-dark {{ $todo['count'] > 0 ? 'pulse-ring' : '' }}">
                    <span class="flex items-center gap-2 text-sm text-gray-600">
                        {{ $todo['label'] }}
                        <svg class="w-4 h-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-accent-strong"
                             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.2 4.8a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06L11.17 10 7.2 5.86a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    {{-- 数字が主役。tabular-nums で桁が増えても位置がぶれないようにする。
                         色はステータスごとに分けず、パレットのアクセント（accent-strong）で統一する --}}
                    <span class="mt-3 text-4xl leading-none font-bold tabular-nums {{ $todo['count'] > 0 ? 'text-accent-strong' : 'text-gray-300' }}">
                        <span data-countup="{{ $todo['count'] }}">{{ number_format($todo['count']) }}</span><span class="text-base font-normal text-gray-400 ml-1.5">件</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- 当月KPI ＋ 月別の発注金額推移。対応事項より下の階層なので、数字は控えめに出す --}}
    <h2 class="text-sm font-bold text-gray-500 mb-3">今月の状況</h2>
    <div class="grid gap-4 mb-8">
        {{-- KPIは横一列の細い帯にして、主役（対応が必要なもの）とぶつからないようにする --}}
        <div class="stagger grid grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl ring-1 ring-ink/5 px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注件数</p>
                <p class="mt-1 text-xl font-bold text-ink tabular-nums"><span data-countup="{{ $kpis['count'] }}">{{ number_format($kpis['count']) }}</span><span class="text-sm font-normal text-gray-400 ml-0.5">件</span></p>
            </div>
            <div class="bg-white rounded-2xl ring-1 ring-ink/5 px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注金額</p>
                <p class="mt-1 text-xl font-bold text-ink tabular-nums"><span data-countup="{{ (int) $kpis['amount'] }}" data-countup-prefix="¥">{{ \App\Support\Money::yen($kpis['amount']) }}</span></p>
            </div>
            <div class="bg-white rounded-2xl ring-1 ring-ink/5 px-5 py-4">
                <p class="text-xs text-gray-500">今月の申請件数</p>
                <p class="mt-1 text-xl font-bold text-ink tabular-nums"><span data-countup="{{ $kpis['applied'] }}">{{ number_format($kpis['applied']) }}</span><span class="text-sm font-normal text-gray-400 ml-0.5">件</span></p>
            </div>
        </div>

        {{-- 月別の発注金額推移（発注日ベース・発注済のみ）。
             JSフレームワークは使わず、座標をPHPで出したインラインSVGのエリアチャートで描く --}}
        @php
            // 描画領域。SVGは横に引き伸ばすので、幅600は「6ヶ月ぶんの目盛り」の意味しかない
            $chartW = 600;
            $chartTop = 15;    // 最大値の線の位置
            $chartBase = 160;  // 0円の線の位置
            $chartH = $chartBase - $chartTop;
            $slot = $chartW / count($trend);

            // 各月の点。x はスロットの中央に置く（下のラベルは grid-cols-6 なので中央で揃う）
            $points = [];
            foreach ($trend as $i => $t) {
                $points[] = [
                    'x' => round($slot * ($i + 0.5), 1),
                    'y' => round($chartBase - ($t['amount'] / $trendMax) * $chartH, 1),
                    'trend' => $t,
                ];
            }

            $line = collect($points)
                ->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L') . " {$p['x']},{$p['y']}")
                ->implode(' ');
            // 塗りは折れ線を0円の線まで落として閉じる
            $area = $line . ' L ' . end($points)['x'] . ",{$chartBase} L " . $points[0]['x'] . ",{$chartBase} Z";
        @endphp

        <div class="bg-white rounded-2xl ring-1 ring-ink/5 px-5 py-4">
            <div class="flex items-baseline justify-between mb-3">
                <p class="text-xs text-gray-500">発注金額の推移（直近6ヶ月・発注済）</p>
                <p class="text-[10px] text-gray-400 tabular-nums">最大 {{ \App\Support\Money::yen($trendMax) }}</p>
            </div>

            <div class="relative h-44">
                <svg viewBox="0 0 {{ $chartW }} 180" preserveAspectRatio="none" class="w-full h-full" aria-hidden="true">
                    <defs>
                        {{-- 折れ線の下をアクセント色のグラデーションで塗る --}}
                        <linearGradient id="trend-fill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--color-accent)" stop-opacity="0.6"/>
                            <stop offset="100%" stop-color="var(--color-accent)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    {{-- 目盛り線（最大・半分・0）。引き伸ばしても線の太さが変わらないよう non-scaling-stroke --}}
                    @foreach ([$chartTop, ($chartTop + $chartBase) / 2, $chartBase] as $y)
                        <line x1="0" x2="{{ $chartW }}" y1="{{ $y }}" y2="{{ $y }}"
                              stroke="var(--color-gray-200)" stroke-width="1" vector-effect="non-scaling-stroke"
                              @if ($y != $chartBase) stroke-dasharray="4 4" @endif />
                    @endforeach

                    <path class="trend-area" d="{{ $area }}" fill="url(#trend-fill)"/>
                    <path class="trend-line" d="{{ $line }}" fill="none" stroke="var(--color-accent-strong)"
                          stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                </svg>

                {{-- ホバーでその月の列がうっすら光る（列全体が当たり判定） --}}
                <div class="absolute inset-0 grid" style="grid-template-columns: repeat({{ count($trend) }}, minmax(0, 1fr))">
                    @foreach ($trend as $t)
                        <div class="rounded transition hover:bg-accent-light/50"
                             title="{{ $t['label'] }}：{{ \App\Support\Money::yen($t['amount']) }} / {{ number_format($t['count']) }}件"></div>
                    @endforeach
                </div>

                {{-- 各月の点。SVGを横に引き伸ばしている（円が楕円になる）ので、点だけはHTMLで置く --}}
                @foreach ($points as $i => $p)
                    <span class="trend-dot absolute -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-white
                                 {{ $p['trend']['is_current'] ? 'w-3 h-3 bg-accent-strong' : 'w-2 h-2 bg-accent-dark' }}"
                          style="left: {{ round($p['x'] / $chartW * 100, 2) }}%; top: {{ round($p['y'] / 180 * 100, 2) }}%;
                                 animation-delay: {{ 0.5 + $i * 0.1 }}s"></span>
                @endforeach
            </div>

            {{-- 月・金額・件数。グラフと同じ6列なので点の真下に並ぶ --}}
            <div class="grid mt-2 text-center" style="grid-template-columns: repeat({{ count($trend) }}, minmax(0, 1fr))">
                @foreach ($trend as $t)
                    <div>
                        <p class="text-xs {{ $t['is_current'] ? 'text-ink font-bold' : 'text-gray-500' }}">{{ $t['label'] }}</p>
                        {{-- 0円の月も ¥0 と出す（空白だと「データが無い」のか「0円」なのか分からない） --}}
                        <p class="text-[10px] tabular-nums {{ $t['amount'] > 0 ? 'text-gray-400' : 'text-gray-300' }}">
                            {{ \App\Support\Money::yen($t['amount']) }}
                        </p>
                        {{-- 金額だけだと高額資材1件で跳ねるので件数も添える --}}
                        <p class="text-[10px] text-gray-400 tabular-nums">{{ number_format($t['count']) }}件</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
