<style>
    body { font-family: ipaexg, sans-serif; font-size: 10pt; color: #000; }
    h1 { font-size: 18pt; text-align: center; margin: 0 0 10px; letter-spacing: 6pt; }

    table { border-collapse: collapse; width: 100%; }
    .header td { vertical-align: top; padding: 0; }
    .header .left { width: 55%; }
    .header .right { width: 45%; }
    .header .label { color: #333; }

    .supplier-name { font-size: 13pt; font-weight: bold; padding-bottom: 4px; }

    .items { margin-top: 14px; }
    .items th, .items td { border: 0.6pt solid #000; padding: 4px 5px; }
    .items th { background: #eee; font-weight: normal; text-align: center; }
    .items td { vertical-align: middle; }
    .num { text-align: right; }
    .center { text-align: center; }
    /* 日付や数量が「2026/07/2 5」のように途中で折り返さないようにする */
    .nowrap { white-space: nowrap; }

    .section-title { margin-top: 16px; margin-bottom: 4px; font-weight: bold; }
    .box { border: 0.6pt solid #000; padding: 6px 8px; }
    .box td { padding: 1px 0; }

    .note { min-height: 50px; }
</style>

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
            <table>
                <tr>
                    <td class="label" style="width: 32%;">発注NO</td>
                    <td>{{ $order->purchaseOrderNo() }}</td>
                </tr>
                <tr>
                    <td class="label">発注日</td>
                    <td>{{ ($order->ordered_at ?? now())->format('Y/m/d') }}</td>
                </tr>
            </table>
            <div style="margin-top: 6px; font-weight: bold;">{{ $company['name'] }}</div>
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
            <th style="width: 30%;">商品名</th>
            <th style="width: 15%;">寸法</th>
            <th style="width: 9%;">ロット</th>
            <th style="width: 9%;">単価</th>
            <th style="width: 11%;">購入数</th>
            <th style="width: 13%;">希望納期</th>
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
                <td class="center nowrap">{{ $order->desired_delivery_date?->format('Y/m/d') }}</td>
                <td></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- 納入先：実際に納入する営業所だけ --}}
<div class="section-title">【納入先】</div>
<div class="box">
    <table>
        <tr>
            <td style="width: 26%; font-weight: bold;">{{ $company['name'] }}　{{ $office->name }}</td>
            <td style="width: 40%;">
                @if ($office->postal_code)〒{{ $office->postal_code }}　@endif{{ $office->address }}
            </td>
            <td>TEL：{{ $office->tel ?: '—' }}　　FAX：{{ $office->fax ?: '—' }}</td>
        </tr>
    </table>
</div>

{{-- 備考欄：業者への連絡事項。mPDF は white-space:pre-wrap を解さないので改行は <br> にする --}}
<div class="section-title">【備考欄】</div>
<div class="box note">{!! nl2br(e($order->supplier_note)) !!}</div>
