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
 * 総務・管理者が「発注待ち」または「発注済」の申請から出せる。
 *
 * **発注書を出す＝実際に業者へ発注する**ので、初回のダウンロードで
 * 「発注待ち」→「発注済」に進み、発注日（ordered_at）が記録される。
 * ステータスが変わる操作なので、リンク（GET）ではなくボタン（POST）で受ける。
 */
class PurchaseOrderController extends Controller
{
    public function download(Request $request, Order $order): Response
    {
        $user = $request->user();

        // 総務・管理者のみ
        abort_unless($user->isGeneralAffairs() || $user->isAdmin(), 403, '発注書を出力できるのは総務・管理者のみです。');

        // 総務の承認が済んでいない申請の発注書は出せない
        abort_unless(
            $order->isPendingOrder() || $order->isOrdered(),
            403,
            '総務が承認した申請（発注待ち・発注済）のみ発注書を出力できます。',
        );

        $order->load(['office', 'supplier', 'items']);

        abort_unless($order->supplier, 404, 'この発注申請には業者が設定されていません。');

        // 初回のダウンロードで発注済にする。2回目以降は再発行なので状態は変えない
        if ($order->isPendingOrder()) {
            $order->update([
                'status' => Order::STATUS_ORDERED,
                'ordered_by' => $user->id,
                'ordered_at' => now(),
            ]);
        }

        $html = view('purchase_orders.pdf', [
            'order' => $order,
            'supplier' => $order->supplier,
            'items' => $order->items,
            'office' => $order->office,
            'company' => config('company'),
            // 発注書を出した人が担当者
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
