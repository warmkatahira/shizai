{{--
    KPIタイルに添える小さな折れ線（直近6ヶ月）。
    $values … 月ごとの数値の配列（古い順）

    推移グラフと同じく、座標はPHPで出して読み込み時に線が引かれる。
    値の大小の「かたち」だけ見せるものなので、目盛りもラベルも付けない。
--}}
@php
    $values = array_values($values);
    $max = max(1, ...$values); // 全部0でも潰れないように
    // viewBox は実際の表示サイズ（下の w-20 h-7 ＝ 80×28px）と同じにする。
    // preserveAspectRatio="none" で引き伸ばすため、比率がずれると末尾の丸が楕円になる
    $w = 80;
    $h = 28;
    $pad = 3;               // 上下の余白（線が枠に張り付かないように）
    $padRight = 2.5;        // 右の余白（末尾の点が欠けないように、点の半径ぶん空ける）
    $stepX = count($values) > 1 ? ($w - $padRight) / (count($values) - 1) : 0;

    $points = [];
    foreach ($values as $i => $value) {
        $points[] = [
            'x' => round($i * $stepX, 1),
            'y' => round($h - $pad - ($value / $max) * ($h - $pad * 2), 1),
        ];
    }

    $last = end($points);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="w-20 h-7 shrink-0" aria-hidden="true">
    <polyline class="spark-line" points="{{ collect($points)->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' ') }}"
              fill="none" stroke="var(--color-accent-dark)" stroke-width="1.5"
              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
    {{-- 末尾（＝今月）に点を置いて現在地を示す。線を引き終わってからポンと出る --}}
    <circle class="spark-dot" cx="{{ $last['x'] }}" cy="{{ $last['y'] }}" r="2" fill="var(--color-accent-strong)"/>
</svg>
