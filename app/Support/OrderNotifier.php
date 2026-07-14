<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPendingApprovalNotification;
use App\Notifications\OrderResultNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * 発注申請の状態に応じて、適切な相手にメール通知を送るヘルパー。
 *
 * メールアドレスは任意（営業所の申請用アカウントは共通で使い回すため、
 * 実在のアドレスを持たないことがある）。宛先が無いユーザーは送信対象から外す。
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
            $managers = self::withEmail($order->office->managers()->get());
            Notification::send($managers, new OrderPendingApprovalNotification($order, 'manager'));

            return;
        }

        if ($order->isPendingAffairs()) {
            $affairs = self::withEmail(
                User::where('role', User::ROLE_GENERAL_AFFAIRS)->where('is_active', true)->get()
            );
            Notification::send($affairs, new OrderPendingApprovalNotification($order, 'affairs'));
        }
    }

    /**
     * 最終結果（発注済 or 却下）を通知する。
     *
     * 申請用アカウントはアドレスを持たないことがあるので、
     * その営業所の所長には必ず送り、申請者にアドレスがあれば申請者にも送る。
     */
    public static function notifyApplicant(Order $order): void
    {
        $recipients = self::withEmail(
            $order->office->managers()->get()->push($order->requester)
        )->unique('id');

        Notification::send($recipients, new OrderResultNotification($order));
    }

    /** 通知先メールアドレスを持つユーザーだけに絞る */
    private static function withEmail(Collection $users): Collection
    {
        return $users->filter(fn (User $user) => filled($user->email))->values();
    }
}
