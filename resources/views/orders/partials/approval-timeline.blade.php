{{--
    承認の経緯（申請 → 所長承認 → 総務承認 → 発注書の作成）。
    データは Order::approvalSteps() が組み立てる。ここは見た目だけ。

    段と段をつなぐ縦線は、CSSのボーダーではなく段ごとの小さなSVG。
    済んだ区間だけアクセント色の線が上から下へ引かれるので、どこまで進んだかが動きで分かる。
--}}
@php
    // 段階の状態ごとの見た目（丸の色 / 文字の色）
    $dotClasses = [
        \App\Models\Order::STEP_DONE => 'bg-accent-strong ring-accent-light',
        \App\Models\Order::STEP_CURRENT => 'bg-white ring-accent-strong',
        \App\Models\Order::STEP_PENDING => 'bg-gray-200 ring-gray-100',
        \App\Models\Order::STEP_SKIPPED => 'bg-gray-200 ring-gray-100',
        \App\Models\Order::STEP_STOPPED => 'bg-orange-500 ring-orange-100',
    ];
    $labelClasses = [
        \App\Models\Order::STEP_DONE => 'text-ink',
        \App\Models\Order::STEP_CURRENT => 'text-accent-strong font-bold',
        \App\Models\Order::STEP_PENDING => 'text-gray-400',
        \App\Models\Order::STEP_SKIPPED => 'text-gray-400',
        \App\Models\Order::STEP_STOPPED => 'text-orange-700 font-bold',
    ];

    $steps = $order->approvalSteps();
@endphp

<ol class="relative ml-2">
    @foreach ($steps as $i => $step)
        <li class="relative pl-6 {{ $loop->last ? '' : 'pb-5' }}">
            {{-- 次の段へつなぐ縦線。最後の段の下には引かない。
                 SVGは置換要素なので top/bottom を両方指定しても高さが決まらず、viewBox の比率
                 （＝100px）で描かれてしまう。段の高さに追従させるため h-full で明示する --}}
            @unless ($loop->last)
                <svg class="absolute left-0 top-2 h-full w-0.5" viewBox="0 0 2 100"
                     preserveAspectRatio="none" aria-hidden="true">
                    <line x1="1" y1="0" x2="1" y2="100" stroke="var(--color-gray-200)"
                          stroke-width="2" vector-effect="non-scaling-stroke"/>
                    {{-- 済んだ区間だけ、上から下へ引かれる線を重ねる --}}
                    @if ($step['state'] === \App\Models\Order::STEP_DONE)
                        <line class="timeline-line" x1="1" y1="0" x2="1" y2="100"
                              stroke="var(--color-accent-strong)" stroke-width="2" vector-effect="non-scaling-stroke"
                              style="animation-delay: {{ 0.1 + $i * 0.25 }}s"/>
                    @endif
                </svg>
            @endunless

            {{-- 縦線（左端から1px）の中心に丸を重ねる --}}
            <span class="absolute -left-1.5 top-1 w-3.5 h-3.5 rounded-full ring-4 {{ $dotClasses[$step['state']] }}"
                  aria-hidden="true"></span>

            <p class="text-sm {{ $labelClasses[$step['state']] }}">
                {{ $step['label'] }}
                @if ($step['state'] === \App\Models\Order::STEP_CURRENT)
                    <span class="ml-1 align-middle text-[10px] bg-accent-light text-accent-strong rounded-full px-2 py-0.5">いまここ</span>
                @endif
            </p>

            @if ($step['at'] || $step['name'])
                <p class="text-xs text-gray-500 tabular-nums">
                    {{ $step['at']?->format('Y/m/d H:i') }}
                    @if ($step['name'])
                        <span class="text-gray-400">{{ $step['at'] ? '／' : '' }}{{ $step['name'] }}</span>
                    @endif
                </p>
            @endif

            @if ($step['note'])
                <p class="text-xs {{ $step['state'] === \App\Models\Order::STEP_STOPPED ? 'text-orange-700' : 'text-gray-400' }} mt-0.5">
                    {{ $step['note'] }}
                </p>
            @endif
        </li>
    @endforeach
</ol>
