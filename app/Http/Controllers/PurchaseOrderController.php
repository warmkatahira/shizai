<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

/**
 * 発注書（PDF）の出力。
 *
 * 1申請＝1業者なので、発注書は1申請につき1枚。
 * 総務・管理者が「発注待ち」または「発注済」の申請から出せる。
 *
 * **発注書を出す＝実際に業者へ発注する**ので、「発注済」に進める操作（issue）と
 * PDFを出すだけの操作（download）を分けている。
 * - issue（POST）… 状態を変えるのでボタン。発注済にしたあと詳細画面へ戻し、
 *   戻った先でダウンロードを始める。**画面がその場で「発注済」に変わる**。
 *   （PDFを直接返すと応答がファイルなのでページが遷移せず、更新するまで
 *   画面が「発注待ち」のままに見えてしまう）
 * - download（GET）… 発注済の申請のPDFを出すだけ。状態は変えないので、
 *   ブラウザの先読みや誤クリックで発注済になる心配がない（再発行もこちら）。
 */
class PurchaseOrderController extends Controller
{
    /** 発注書を作成する（＝業者へ発注する）。発注済にして詳細画面へ戻す */
    public function issue(Request $request, Order $order): RedirectResponse
    {
        $this->assertCanIssue($request, $order);

        // 初回のみ発注済にする。2回目以降は再発行なので状態は変えない
        if ($order->isPendingOrder()) {
            $order->update([
                'status' => Order::STATUS_ORDERED,
                'ordered_by' => $request->user()->id,
                'ordered_at' => now(),
            ]);
        }

        // 戻った先の画面でダウンロードを始める（詳細画面の hidden iframe）
        return redirect()->route('orders.show', $order)
            ->with('download_purchase_order', true)
            ->with('status', '発注書を作成しました。ダウンロードを開始します。');
    }

    /** 発注書PDFを出す（発注済のみ。状態は変えない） */
    public function download(Request $request, Order $order): Response
    {
        $this->assertCanIssue($request, $order);

        // PDFを出すだけの入口なので、まだ発注していないものは対象外
        // （発注書を出す＝発注する、なので必ず issue を通ってから）
        abort_unless($order->isOrdered(), 403, 'まだ発注していない申請の発注書は出力できません。');

        $order->load(['office', 'supplier', 'shippingDestination', 'items']);

        $html = view('purchase_orders.pdf', [
            'order' => $order,
            'supplier' => $order->supplier,
            'items' => $order->items,
            'office' => $order->office,
            // 直送のときだけ入る。null なら納入先は発注元の営業所
            'destination' => $order->shippingDestination,
            'company' => config('company'),
            // 担当者＝いま出力した人。再発行なら再発行した人の氏名が入る
            // （業者からの問い合わせ先は「その発注書を出した人」であってほしいため）
            'personInCharge' => $request->user()->name,
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
            // 納入先・備考欄＋ページ番号はページ下端に固定（ビューの htmlpagefooter）。
            // その高さぶんを下の余白として空けておかないと本文と重なる
            'margin_bottom' => 52,
            'margin_footer' => 6,
            // フォントのキャッシュ先（storage 配下は書き込み可）
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle('発注書 ' . $order->purchaseOrderNo());
        $mpdf->WriteHTML($html);

        $filename = sprintf(
            '資材発注書_%s_%s_%s.pdf',
            $order->purchaseOrderNo(),
            $order->supplier->name,
            $order->ordered_at->format('Ymd'),
        );

        return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($filename),
        ]);
    }

    /** 発注書を扱える人・状態か（作成・ダウンロードで共通） */
    private function assertCanIssue(Request $request, Order $order): void
    {
        // 総務・管理者のみ
        abort_unless($request->user()->canIssuePurchaseOrder(), 403, '発注書を出力できるのは総務・管理者のみです。');

        // 総務の承認が済んでいない申請の発注書は出せない
        abort_unless(
            $order->isPendingOrder() || $order->isOrdered(),
            403,
            '総務が承認した申請（発注待ち・発注済）のみ発注書を出力できます。',
        );

        abort_unless($order->supplier_id, 404, 'この発注申請には業者が設定されていません。');
    }
}
