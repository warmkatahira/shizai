{{--
    iOS風のトグルスイッチ（オンで緑）。マスタの編集フォームと操作ログの設定で共有する。
    JSフレームワークは使わず、peer（チェック状態）だけで見た目を切り替える。

    $name     … input の name
    $label    … 添える文言（HTMLを含められる）
    $checked  … 初期状態
    $between  … true にすると「文言を左・トグルを右」に離して並べる（一覧形式の設定画面向け）

    ※ @include は呼び出し元の変数をそのまま引き継ぐ。$value のような
      ありふれた名前を「省略時はこの既定値」で受けると、呼び出し元の
      @foreach ($... as $value => $label) が残した値を拾ってしまう
      （実際に is_active の value が 'sales' になってオフで保存される不具合が出た）。
      送る値は常に 1 なので変数にしない。
--}}
@php
    $between = $between ?? false;
@endphp

<label class="flex items-center {{ $between ? 'justify-between gap-4' : 'gap-3' }} cursor-pointer text-sm text-gray-700">
    @if ($between)
        <span class="text-ink">{!! $label !!}</span>
    @endif

    <span class="relative inline-flex items-center shrink-0">
        <input autocomplete="off" type="checkbox" name="{{ $name }}" value="1"
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
