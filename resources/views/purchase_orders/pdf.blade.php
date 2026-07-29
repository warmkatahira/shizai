<style>
    /* 納入先・備考欄をページ下端に固定するため、フッターとして出す（名前は html_ + name 属性） */
    @page { odd-footer-name: html_boxes; even-footer-name: html_boxes; }

    body { font-family: ipaexg, sans-serif; font-size: 10pt; color: #000; }
    h1 { font-size: 18pt; font-weight: normal; text-align: center; margin: 0 0 24px; letter-spacing: 6pt; }

    table { border-collapse: collapse; width: 100%; }

    /* 右上の伝票情報（発注NO・発注日・希望納期）。細枠の小さな表にして伝票らしくする */
    .meta { border-collapse: collapse; font-size: 9pt; width: 100%; }
    .meta th, .meta td { border: 0.5pt solid #666; padding: 3px 8px; font-weight: normal; }
    .meta th { background: #f2f2f2; color: #333; text-align: left; width: 45%; }
    .meta td { text-align: right; }
    /* 業者が最初に見るのは納期。文字の大きさは他と揃え、網掛けだけ濃くして目立たせる */
    .meta .due th { background: #e4e4e4; color: #000; }

    .header td { vertical-align: top; padding: 0; }
    .header .left { width: 55%; }
    .header .right { width: 45%; text-align: right; }
    /* 社名は一段大きく、住所・連絡先は小さく薄く（読む順番を作る） */
    .org-name { font-size: 12pt; padding-bottom: 4px; }
    .org-detail { font-size: 8.5pt; color: #333; }

    /*
     * 明細。全セルを同じ黒罫で囲む「格子」は情報が多く見えて読みにくいので、
     * 外枠を太く／ヘッダーの下を太く／行の区切りは細い薄グレー、縦罫は無し にする。
     * 列は右揃え・左揃えで十分に分かれる。
     */
    .items { margin-top: 20px; font-size: 9pt; border: 1pt solid #000; }
    .items th { background: #eee; font-weight: normal; text-align: center; padding: 5px; border-bottom: 1pt solid #000; }
    .items td { padding: 5px; border-bottom: 0.3pt solid #ccc; vertical-align: middle; }
    /* 明細が多いときに行を追えるよう、1行おきに薄い地を敷く（濃くするとFAXで潰れる） */
    .items tr.alt td { background: #f7f7f7; }
    .num { text-align: right; }
    .center { text-align: center; }
    /* 日付や数量が「2026/07/2 5」のように途中で折り返さないようにする */
    .nowrap { white-space: nowrap; }

    /* 【納入先】【備考欄】の見出し。黒地に白抜きの帯にして帳票らしく締める */
    .section-title { background: #000; color: #fff; padding: 2px 8px; font-size: 8.5pt; margin: 0 0 4px; }

    /* 納入先・備考欄の箱。外枠だけのシンプルな箱。
       左右のセルは同じテーブル行に置くので高さが常に一致する。
       height で固定の高さを確保する（文字数に依らず揃った見た目にする） */
    .fill-box { border: 0.6pt solid #000; padding: 8px; height: 110px; vertical-align: top; }
    .fill-box div { padding: 1px 0; }

    .page-no { text-align: right; font-size: 8pt; color: #333; padding-top: 3px; }
</style>

{{-- 発注NO・発注日・希望納期はタイトルより上の右端に置く（希望納期は発注単位で1つ） --}}
<table style="width: 100%; margin-bottom: 6px;">
    <tr>
        <td style="width: 62%;"></td>
        <td style="width: 38%;">
            <table class="meta">
                <tr><th>発注NO</th><td>{{ $order->purchaseOrderNo() }}</td></tr>
                <tr><th>発注日</th><td>{{ ($order->ordered_at ?? now())->format('Y/m/d') }}</td></tr>
                <tr class="due"><th>希望納期</th><td>{{ $order->desired_delivery_date?->format('Y/m/d') ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h1>発 注 書</h1>

{{-- ヘッダー：左＝発注先の業者、右＝自社（本社）の情報 --}}
<table class="header">
    <tr>
        <td class="left">
            {{-- 宛名だけは正式名称（株式会社まで）。未入力なら表示名を使う --}}
            <div class="org-name">{{ $supplier->formalName() }} 御中</div>
            <div class="org-detail">TEL：{{ $supplier->phone ?: '—' }}</div>
            <div class="org-detail">FAX：{{ $supplier->fax ?? '—' }}</div>
        </td>
        <td class="right">
            <div class="org-name">{{ $company['name'] }}</div>
            <div class="org-detail">{{ $company['address'] }}</div>
            <div class="org-detail">担当：{{ $personInCharge }}</div>
            <div class="org-detail">TEL：{{ $company['tel'] }}</div>
            <div class="org-detail">FAX：{{ $company['fax'] }}</div>
        </td>
    </tr>
</table>

{{-- 明細：この業者に発注する商品だけ --}}
<table class="items">
    <thead>
        <tr>
            <th style="width: 38%;">商品名</th>
            <th style="width: 17%;">寸法</th>
            <th style="width: 10%;">ロット</th>
            <th style="width: 11%;">単価</th>
            <th style="width: 11%;">購入数</th>
            <th style="width: 13%;">返信納期</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            {{-- 偶数行だけ薄い地（mPDF の擬似クラス頼みにせず、行にクラスを付ける） --}}
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td>{{ $item->material_name }}</td>
                <td class="center">{{ $item->size_text ?: ($item->sizeText() ?? '') }}</td>
                <td class="num nowrap">{{ $item->minLotText() ?? '' }}</td>
                <td class="num nowrap">{{ \App\Support\Money::yen($item->unit_price, '') }}</td>
                <td class="num nowrap">{{ number_format($item->quantity) }} {{ $item->unit }}</td>
                <td></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- 納入先（左）と備考欄（右）を横並びにする。
     明細の量に関係なく**ページの一番下**に来るよう、mPDF のページフッターとして出す
     （通常の流れに置くと、明細が少ないときに紙の真ん中で終わってしまう）。
     下の余白は PurchaseOrderController の margin_bottom / margin_footer で確保している。

     見出し行と枠行を分け、枠行は同じ行に置くので左右の高さが常に揃う。
     真ん中の細い列は左右の箱の間の隙間。 --}}
<htmlpagefooter name="boxes">
    <table style="width: 100%;">
        <tr>
            <td style="width: 48%;"><div class="section-title">納入先</div></td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;"><div class="section-title">備考欄</div></td>
        </tr>
        <tr>
            {{-- 納入先：実際に納入する営業所だけ。項目ごとに改行して縦に並べる --}}
            <td class="fill-box">
                <div>{{ $company['name'] }}　{{ $office->name }}</div>
                <div>@if ($office->postal_code)〒{{ $office->postal_code }}　@endif{{ $office->address }}</div>
                <div>TEL：{{ $office->tel ?: '—' }}</div>
                <div>FAX：{{ $office->fax ?: '—' }}</div>
            </td>
            <td></td>
            {{-- 備考欄：業者への連絡事項。mPDF は white-space:pre-wrap を解さないので改行は <br> にする --}}
            <td class="fill-box">{!! nl2br(e($order->supplier_note)) !!}</td>
        </tr>
    </table>
    {{-- 複数ページになったとき用。{PAGENO} と {nb} は mPDF が差し替える --}}
    <div class="page-no">{PAGENO} / {nb}</div>
</htmlpagefooter>
