{{--
    いま効いている絞り込みをタグで並べる。✕ を押すとその条件だけ外れる。
    呼び出し側で $chips を作って渡す：
        ['label' => 'ステータス：総務承認待ち', 'remove' => ['status' => '']]

    ※ 条件を外すのは「キーを消す」ではなく「空で送る」。キーが無いと初期値
      （当月・総務承認待ち・有効 など）が再適用されて外れないため。
    ※ page は消して1ページ目に戻す（絞り込みが変われば件数も変わるので）。
--}}
@php
    $chips = collect($chips ?? [])->filter(fn ($chip) => filled($chip['label'] ?? null));
@endphp

@if ($chips->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">絞り込み中</span>

        @foreach ($chips as $chip)
            {{-- ページ背景（ベージュグレー）の上に置くので、薄い塗りだと沈む。
                 アクセント地＋濃い文字（コントラスト 7.4:1）ではっきり見せる --}}
            <a href="{{ request()->fullUrlWithQuery($chip['remove'] + ['page' => null]) }}"
               class="group inline-flex items-center gap-1.5 rounded-full bg-accent text-ink ring-1 ring-accent-dark text-xs pl-3 pr-2 py-1 transition hover:bg-accent-dark">
                {{ $chip['label'] }}
                <span class="text-accent-strong group-hover:text-ink" aria-hidden="true">✕</span>
                <span class="sr-only">この条件を外す</span>
            </a>
        @endforeach
    </div>
@endif
