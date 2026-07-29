{{--
    マスタ管理のCSV取り込みパネル（営業所・業者・カテゴリ・単位・資材で共有）。
    出したCSVをExcelで直して戻す使い方を想定している。

    $label      … 「資材」「業者」など。文言に埋め込む
    $importUrl  … 取り込み先のURL
    $extraNotes … このマスタ固有の注意書き（省略可）
--}}
@php
    $extraNotes = $extraNotes ?? [];
@endphp

<details class="bg-white shadow rounded-lg mb-6" {{ $errors->any() ? 'open' : '' }}>
    <summary class="px-6 py-4 cursor-pointer text-sm font-medium select-none">
        📤 CSVから取り込む（追加・更新）
    </summary>
    <div class="px-6 pb-6 pt-1 border-t border-gray-100">
        <ul class="text-xs text-gray-500 space-y-1 mb-4 list-disc list-inside">
            <li>まず <span class="font-medium">CSVダウンロード</span> で現在の{{ $label }}を出し、Excelで直してから取り込むのが確実です。</li>
            <li><span class="font-medium">ID列</span>が入っている行はその{{ $label }}を<span class="font-medium">更新</span>、ID列が空の行は<span class="font-medium">新規追加</span>になります。</li>
            @foreach ($extraNotes as $note)
                <li>{!! $note !!}</li>
            @endforeach
            <li><span class="font-medium">1行でもエラーがあれば、何も取り込みません。</span>行番号つきでエラーを表示します。</li>
            <li>CSVに書かなかった{{ $label }}は、そのまま残ります（削除はされません）。外したい{{ $label }}は「有効」を <span class="font-medium">いいえ</span> にしてください。</li>
        </ul>

        <form method="POST" action="{{ $importUrl }}" enctype="multipart/form-data"
              onsubmit="return confirm('このCSVで{{ $label }}マスタを追加・更新します。よろしいですか？')">
            @csrf
            {{-- ドラッグ＆ドロップ用の枠。label で input を包んでいるので、
                 JSが動かなくてもクリックでファイル選択ダイアログが開く。
                 ドロップの処理は layouts/app.blade.php の共通スクリプト --}}
            <label data-dropzone data-dropzone-reject="CSVファイル（.csv）を落としてください。"
                   class="dropzone flex flex-col items-center justify-center gap-1 w-full px-6 py-8 mb-3
                          border-2 border-dashed border-gray-300 rounded-lg cursor-pointer text-center
                          hover:border-accent-dark hover:bg-accent-light/30 transition-colors">
                <input type="file" name="file" accept=".csv,text/csv" required class="sr-only">
                <span class="text-2xl" aria-hidden="true">📄</span>
                <span data-dropzone-label class="text-sm text-gray-600">
                    CSVファイルをここにドラッグ＆ドロップ
                </span>
                <span class="text-xs text-gray-400">クリックしてファイルを選ぶこともできます</span>
            </label>

            <button class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">取り込む</button>
        </form>
    </div>
</details>
