<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPendingApprovalNotification;
use App\Notifications\OrderResultNotification;
use Illuminate\Support\Facades\Notification;

/**
 * 発注申請の状態に応じて、適切な相手にメール通知を送るヘルパー。
 */
class OrderNotifier
{
    /**
     * 現在のステータスに応じて「次の承認者」へ通知する。
     * - 所長承認待ち → その営業所の所長へ
     * - 総務承認待ち → 総務ユーザー全員へ
     */
    public static function notifyNextApprover(Order $order): void
    {
        if ($order->isPendingManager()) {
            $managers = $order->office->managers()->get();
            Notification::send($managers, new OrderPendingApprovalNotification($order, 'manager'));
            return;
        }

        if ($order->isPendingAffairs()) {
            $affairs = User::where('role', User::ROLE_GENERAL_AFFAIRS)
                ->where('is_active', true)->get();
            Notification::send($affairs, new OrderPendingApprovalNotification($order, 'affairs'));
        }
    }

    /**
     * 最終結果（発注済 or 却下）を申請者へ通知する。
     */
    public static function notifyApplicant(Order $order): void
    {
        $order->requester->notify(new OrderResultNotification($order));
    }
}
