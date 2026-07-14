<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\OrderNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 発注申請の承認・差し戻し・却下を扱うコントローラー。
 *
 * フロー:
 *   所長承認待ち --[所長 承認]--> 総務承認待ち --[総務 承認]--> 発注待ち --[発注書の作成]--> 発注済
 *   所長承認待ち --[総務 特例承認]-------------------------> 発注待ち
 *   各段階で 却下（終了）／差し戻し（申請者に戻して修正・再申請）ができる
 *
 * 誰が何をできるかの判定は Order のメソッド（canBe〜By）に集約している。
 */
class OrderApprovalController extends Controller
{
    /** 所長による一次承認：所長承認待ち → 総務承認待ち */
    public function managerApprove(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeManagerApprovedBy($user), 403, 'この申請を承認する権限がありません。');

        $order->update([
            'status' => Order::STATUS_PENDING_AFFAIRS,
            'manager_approved_by' => $user->id,
            'manager_approved_at' => now(),
        ]);

        // 総務へ通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyNextApprover($order);

        return back()->with('status', '承認しました。総務へ申請が回りました。');
    }

    /**
     * 総務による承認：総務承認待ち → 発注待ち
     * 実際に業者へ発注する（＝発注書を出す）と「発注済」になる。
     */
    public function affairsApprove(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeAffairsApprovedBy($user), 403, 'この申請を承認する権限がありません。');

        $order->update([
            'status' => Order::STATUS_PENDING_ORDER,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        // 申請者へ通知
        $order->load(['requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '承認しました。発注書を作成すると「発注済」になります。');
    }

    /** 総務による特例承認：所長承認待ち → 発注待ち（所長を飛ばす。理由必須） */
    public function specialApprove(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeSpecialApprovedBy($user), 403, '特例承認を行う権限がありません。');

        $validated = $request->validate([
            'special_reason' => ['required', 'string', 'max:1000'],
        ], [], ['special_reason' => '特例承認の理由']);

        $order->update([
            'status' => Order::STATUS_PENDING_ORDER,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'is_special_approval' => true,
            'special_reason' => $validated['special_reason'],
        ]);

        // 申請者へ通知
        $order->load(['requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '特例承認しました。発注書を作成すると「発注済」になります。');
    }

    /**
     * 差し戻し（所長・総務のいずれか。理由必須）。
     *
     * 却下と違い、ここで終わりではない。申請者が内容を修正して再申請できる状態にする。
     * 発注書をまだ出していない「発注待ち」も、総務なら差し戻せる。
     * 承認履歴（所長承認・総務承認・特例承認）は再申請時にクリアされる（OrderController::update）。
     */
    public function returnToRequester(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeReturnedBy($user), 403, 'この申請を差し戻す権限がありません。');

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:1000'],
        ], [], ['return_reason' => '差し戻しの理由']);

        $order->update([
            'status' => Order::STATUS_RETURNED,
            'return_reason' => $validated['return_reason'],
            'returned_by' => $user->id,
            'returned_at' => now(),
        ]);

        // 申請者（＋その営業所の所長）へ通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '申請を差し戻しました。申請者が内容を修正して再申請できます。');
    }

    /** 却下（所長・総務のいずれか。理由必須）。却下されたらそこで終了 */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->canBeRejectedBy($user), 403, 'この申請を却下する権限がありません。');

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ], [], ['reject_reason' => '却下理由']);

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'reject_reason' => $validated['reject_reason'],
            'rejected_by' => $user->id,
        ]);

        // 申請者へ通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '申請を却下しました。');
    }
}
