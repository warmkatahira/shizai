<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

/**
 * 発注書（PDF）の出力。
 *
 * 1申請＝1業者なので、発注書は1申請につき1枚。
 * 発注済（ordered）の申請だけ、総務・管理者が出力できる。
 */
class PurchaseOrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $user = $request->user();

        // 総務・管理者のみ
        abort_unless($user->isGeneralAffairs() || $user->isAdmin(), 403, '発注書を出力できるのは総務・管理者のみです。');

        // 発注が確定していないものは出せない
        abort_unless($order->isOrdered(), 403, '発注済の申請のみ発注書を出力できます。');

        $order->load(['office', 'supplier', 'items']);

        abort_unless($order->supplier, 404, 'この発注申請には業者が設定されていません。');

        $html = view('purchase_orders.pdf', [
            'order' => $order,
            'supplier' => $order->supplier,
            'items' => $order->items,
            'office' => $order->office,
            'company' => config('company'),
            // 発注書作成ボタンを押した人が担当者
            'personInCharge' => $user->name,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'ja',
            'format' => 'A4',
            'fontDir' => [storage_path('fonts')],
            'fontdata' => [
                'ipaexg' => ['R' => 'ipaexg.ttf'],
            ],
            'default_font' => 'ipaexg',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            // フォントのキャッシュ先（storage 配下は書き込み可）
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle('発注書 ' . $order->purchaseOrderNo());
        $mpdf->WriteHTML($html);

        $filename = sprintf('発注書_%s_%s.pdf', $order->purchaseOrderNo(), $order->supplier->name);

        return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($filename),
        ]);
    }
}
