{{--
    ロゴのマーク（買い物カート）。ファビコン `public/favicon.svg` と同じ絵。

    ※ 絵を持つのは favicon.svg と**このファイルの2か所だけ**。
      ヘッダーなど動かさない場所は `<img src="/favicon.svg">` で使い回す。
      中の線や丸を個別にアニメーションさせるにはHTMLへ直接書くしかないため、
      ログイン画面のためだけにここへ複製している。絵を直すときは両方直すこと。

    渡すもの：
      $class   … <svg> に付けるクラス（大きさなど）
      $animate … true にすると読み込み時に「カートの線 → 車輪 → ＋バッジ」の順に組み上がる
--}}
@php
    $animate = $animate ?? false;
    $class = $class ?? 'w-14 h-14';
@endphp

<svg viewBox="0 0 512 512" class="{{ $class }}" aria-hidden="true">
    <rect x="0" y="0" width="512" height="512" rx="116" fill="#34251F"/>

    {{-- カート本体（1本の連続パス）。線が描かれる --}}
    <path class="{{ $animate ? 'logo-cart' : '' }}"
          d="M92 132 h58 l42 214 h150 l46 -168 h-210"
          fill="none" stroke="#EBE9EA" stroke-width="28" stroke-linecap="round" stroke-linejoin="round"/>

    {{-- 車輪。線を引き終わる頃にポンと出る --}}
    <circle class="{{ $animate ? 'logo-pop' : '' }}" cx="220" cy="404" r="26" fill="#EBE9EA" style="animation-delay: 0.55s"/>
    <circle class="{{ $animate ? 'logo-pop' : '' }}" cx="356" cy="404" r="26" fill="#EBE9EA" style="animation-delay: 0.65s"/>

    {{-- 「＋」のバッジ。丸が出てから縦線・横線を引く --}}
    <circle class="{{ $animate ? 'logo-pop' : '' }}" cx="262" cy="243" r="66" fill="#D5BDAE" style="animation-delay: 0.8s"/>
    <line class="{{ $animate ? 'logo-plus' : '' }}" x1="262" y1="206" x2="262" y2="280"
          stroke="#34251F" stroke-width="26" stroke-linecap="round" style="animation-delay: 1s"/>
    <line class="{{ $animate ? 'logo-plus' : '' }}" x1="225" y1="243" x2="299" y2="243"
          stroke="#34251F" stroke-width="26" stroke-linecap="round" style="animation-delay: 1.1s"/>
</svg>
