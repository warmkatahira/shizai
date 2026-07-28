{{--
    KPIタイルに添える小さな折れ線（直近6ヶ月）。
    $values … 月ごとの数値の配列（古い順）

    推移グラフと同じく、座標はPHPで出して読み込み時に線が引かれる。
    値の大小の「かたち」だけ見せるものなので、目盛りもラベルも付けない。
--}}
@php
    $values = array_values($values);
    $max = max(1, ...$values); // 全部0でも潰れないように
    $w = 100;
    $h = 28;
    $pad = 3;               // 上下の余白（線が枠に張り付かないように）
    $stepX = count($values) > 1 ? $w / (count($values) - 1) : 0;

    $points = [];
    foreach ($values as $i => $value) {
        $points[] = round($i * $stepX, 1) . ',' . round($h - $pad - ($value / $max) * ($h - $pad * 2), 1);
    }
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="w-20 h-7 shrink-0" aria-hidden="true">
    <polyline class="spark-line" points="{{ implode(' ', $points) }}"
              fill="none" stroke="var(--color-accent-dark)" stroke-width="1.5"
              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
</svg>
