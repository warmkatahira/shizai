{{--
    並び替えできる列見出し（発注申請一覧）。

    $key       … 並び順のキー（OrderController::SORTS のキー）
    $label     … 見出しの文言
    $default   … その列を初めて押したときの向き（省略時 asc）
    $align     … 'right' で右寄せ（点数など）
    $sort      … いま効いている並び順のキー（コントローラーから）
    $direction … いま効いている向き（同上）

    条件は fullUrlWithQuery でそのまま引き継ぐ。押すたびに page を捨てて1ページ目へ戻す
    （2ページ目のまま並び替えると、見えている範囲が変わって迷子になるため）。
--}}
@php
    $default = $default ?? 'asc';
    $align = $align ?? 'left';
    $isCurrent = $sort === $key;
    // 今その列で並んでいるなら逆向き、そうでなければその列の初回の向き
    $next = $isCurrent ? ($direction === 'asc' ? 'desc' : 'asc') : $default;
@endphp

<th class="px-4 py-3 {{ $align === 'right' ? 'text-right' : '' }}"
    aria-sort="{{ $isCurrent ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => $next, 'page' => null]) }}"
       class="inline-flex items-center gap-1 hover:text-ink {{ $isCurrent ? 'text-ink font-bold' : '' }}">
        {{ $label }}
        {{-- 並び順が付いていない列にも薄い印を出しておく（押せることが分かるように） --}}
        <span class="text-[10px] leading-none {{ $isCurrent ? 'text-accent-strong' : 'text-gray-300' }}"
              aria-hidden="true">{{ $isCurrent && $direction === 'asc' ? '▲' : '▼' }}</span>
    </a>
</th>
