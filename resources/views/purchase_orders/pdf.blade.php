<style>
    body { font-family: ipaexg, sans-serif; font-size: 10pt; color: #000; }
    h1 { font-size: 18pt; font-weight: normal; text-align: center; margin: 0 0 24px; letter-spacing: 6pt; }

    table { border-collapse: collapse; width: 100%; }
    .header td { vertical-align: top; padding: 0; }
    .header .left { width: 55%; }
    .header .right { width: 45%; text-align: right; }
    .header .label { color: #333; }

    .supplier-name { padding-bottom: 4px; }

    .items { margin-top: 24px; font-size: 9pt; }
    .items th, .items td { border: 0.6pt solid #000; padding: 4px 5px; }
    .items th { background: #eee; font-weight: normal; text-align: center; }
    .items td { vertical-align: middle; }
    .num { text-align: right; }
    .center { text-align: center; }
    /* 日付や数量が「2026/07/2 5」のように途中で折り返さないようにする */
    .nowrap { white-space: nowrap; }

    .section-title { margin-top: 0; margin-bottom: 4px; }

    /* 納入先・備考欄の箱。外枠だけのシンプルな箱。
       左右のセルは同じテーブル行に置くので高さが常に一致する。
       height で固定の高さを確保する（文字数に依らず揃った見た目にする） */
    .fill-box { border: 0.6pt solid #000; padding: 8px; height: 110px; vertical-align: top; }
    .fill-box div { padding: 1px 0; }
</style>

{{-- 発注NO・発注日・希望納期はタイトルより上の右端に置く（希望納期は発注単位で1つ） --}}
<table style="width: 100%; margin-bottom: 2px;">
    <tr><td style="text-align: right;">発注NO：{{ $order->purchaseOrderNo() }}</td></tr>
    <tr><td style="text-align: right;">発注日：{{ ($order->ordered_at ?? now())->format('Y/m/d') }}</td></tr>
    <tr><td style="text-align: right;">希望納期：{{ $order->desired_delivery_date?->format('Y/m/d') ?? '—' }}</td></tr>
</table>

<h1>発 注 書</h1>

{{-- ヘッダー：左＝発注先の業者、右＝自社（本社）の情報 --}}
<table class="header">
    <tr>
        <td class="left">
            <div class="supplier-name">{{ $supplier->name }} 御中</div>
            <div>TEL：{{ $supplier->phone ?: '—' }}</div>
            <div>FAX：{{ $supplier->fax ?? '—' }}</div>
        </td>
        <td class="right">
            <div>{{ $company['name'] }}</div>
            <div>{{ $company['address'] }}</div>
            <div>担当：{{ $personInCharge }}</div>
            <div>TEL：{{ $company['tel'] }}</div>
            <div>FAX：{{ $company['fax'] }}</div>
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
            <tr>
                <td>{{ $item->material_name }}</td>
                <td class="center nowrap">{{ $item->sizeText() ?? '' }}</td>
                <td class="num nowrap">{{ $item->minLotText() ?? '' }}</td>
                <td class="num nowrap">{{ \App\Support\Money::yen($item->unit_price, '') }}</td>
                <td class="num nowrap">{{ number_format($item->quantity) }} {{ $item->unit }}</td>
                <td></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- 納入先（左）と備考欄（右）を横並びにする。明細テーブルから少し離す。
     見出し行と枠行を分け、枠行は同じ行に置くので左右の高さが常に揃う。
     真ん中の細い列は左右の箱の間の隙間。 --}}
<table style="width: 100%; margin-top: 28px;">
    <tr>
        <td style="width: 48%;"><div class="section-title">【納入先】</div></td>
        <td style="width: 4%;"></td>
        <td style="width: 48%;"><div class="section-title">【備考欄】</div></td>
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
