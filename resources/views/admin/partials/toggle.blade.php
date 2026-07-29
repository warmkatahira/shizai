{{--
    iOS風のトグルスイッチ（オンで緑）。マスタの編集フォームと操作ログの設定で共有する。
    JSフレームワークは使わず、peer（チェック状態）だけで見た目を切り替える。

    $name     … input の name
    $label    … 添える文言（HTMLを含められる）
    $checked  … 初期状態
    $value    … 送る値（省略時 1）
    $between  … true にすると「文言を左・トグルを右」に離して並べる（一覧形式の設定画面向け）
--}}
@php
    $value = $value ?? '1';
    $between = $between ?? false;
@endphp

<label class="flex items-center {{ $between ? 'justify-between gap-4' : 'gap-3' }} cursor-pointer text-sm text-gray-700">
    @if ($between)
        <span class="text-ink">{!! $label !!}</span>
    @endif

    <span class="relative inline-flex items-center shrink-0">
        <input autocomplete="off" type="checkbox" name="{{ $name }}" value="{{ $value }}"
               class="sr-only peer" {{ $checked ? 'checked' : '' }}>
        <span class="block w-11 h-6 rounded-full bg-gray-300 transition-colors peer-checked:bg-[#34C759]
                     peer-focus-visible:ring-2 peer-focus-visible:ring-[#34C759]/40"></span>
        <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow
                     transition-transform peer-checked:translate-x-5"></span>
    </span>

    @unless ($between)
        <span>{!! $label !!}</span>
    @endunless
</label>
