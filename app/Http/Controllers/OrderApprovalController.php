<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\OrderNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 発注申請の承認・却下を扱うコントローラー。
 *
 * フロー:
 *   所長承認待ち --[所長 承認]--> 総務承認待ち --[総務 発注]--> 発注済
 *   所長承認待ち --[総務 特例承認]------------------------> 発注済
 *   （各段階で 却下 が可能）
 */
class OrderApprovalController extends Controller
{
    /** 所長による一次承認：所長承認待ち → 総務承認待ち */
    public function managerApprove(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOfficeManager($request, $order);

        abort_unless($order->isPendingManager(), 409, 'この申請は所長承認待ちではありません。');

        $order->update([
            'status' => Order::STATUS_PENDING_AFFAIRS,
            'manager_approved_by' => $request->user()->id,
            'manager_approved_at' => now(),
        ]);

        // 総務へ通知
        $order->load(['office', 'requester', 'items']);
        OrderNotifier::notifyNextApprover($order);

        return back()->with('status', '承認しました。総務へ申請が回りました。');
    }

    /** 総務による発注確定：総務承認待ち → 発注済 */
    public function affairsApprove(Request $request, Order $order): RedirectResponse
    {
        $this->ensureGeneralAffairs($request);

        abort_unless($order->isPendingAffairs(), 409, 'この申請は総務承認待ちではありません。');

        $order->update([
            'status' => Order::STATUS_ORDERED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // 申請者へ通知
        $order->load(['requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '発注を確定しました。');
    }

    /** 総務による特例承認：所長承認待ち → 発注済（所長を飛ばす。理由必須） */
    public function specialApprove(Request $request, Order $order): RedirectResponse
    {
        $this->ensureGeneralAffairs($request);

        abort_unless($order->isPendingManager(), 409, '特例承認は所長承認待ちの申請にのみ行えます。');

        $validated = $request->validate([
            'special_reason' => ['required', 'string', 'max:1000'],
        ], [], ['special_reason' => '特例承認の理由']);

        $order->update([
            'status' => Order::STATUS_ORDERED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'is_special_approval' => true,
            'special_reason' => $validated['special_reason'],
        ]);

        // 申請者へ通知
        $order->load(['requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '特例承認で発注を確定しました。');
    }

    /** 却下（所長・総務のいずれか。理由必須） */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        $this->ensureCanReject($request, $order);

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ], [], ['reject_reason' => '却下理由']);

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'reject_reason' => $validated['reject_reason'],
            'rejected_by' => $request->user()->id,
        ]);

        // 申請者へ通知
        $order->load(['requester', 'items']);
        OrderNotifier::notifyApplicant($order);

        return back()->with('status', '申請を却下しました。');
    }

    // ---- 権限チェック ----

    /** 同じ営業所の所長であることを確認 */
    private function ensureOfficeManager(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless(
            $user->isManager() && $user->office_id === $order->office_id,
            403,
            'この申請を承認する権限がありません。'
        );
    }

    /** 総務であることを確認 */
    private function ensureGeneralAffairs(Request $request): void
    {
        abort_unless($request->user()->isGeneralAffairs(), 403, '総務のみ実行できます。');
    }

    /**
     * 却下できるのは：
     * - 所長承認待ち：その営業所の所長、または総務
     * - 総務承認待ち：総務
     */
    private function ensureCanReject(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($order->isPendingManager()) {
            $ok = ($user->isManager() && $user->office_id === $order->office_id)
                || $user->isGeneralAffairs();
        } elseif ($order->isPendingAffairs()) {
            $ok = $user->isGeneralAffairs();
        } else {
            $ok = false; // すでに確定・却下済みのものは却下不可
        }

        abort_unless($ok, 403, 'この申請を却下する権限がありません。');
    }
}
