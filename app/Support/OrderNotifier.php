<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPendingApprovalNotification;
use App\Notifications\OrderResultNotification;
use App\Notifications\PostOrderNoteUpdatedNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

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
            self::dispatch($managers, new OrderPendingApprovalNotification($order, 'manager'), $order);

            return;
        }

        if ($order->isPendingAffairs()) {
            self::dispatch(self::withEmail(self::generalAffairs()), new OrderPendingApprovalNotification($order, 'affairs'), $order);
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

        self::dispatch($recipients, new OrderResultNotification($order), $order);
    }

    /**
     * 発注者メモの更新を通知する。
     *
     * 宛先は「その営業所の所長 ＋ 申請者 ＋ 総務全員」。
     * 申請者が所長本人なら重複するので、unique で1通にまとまる（＝所長だけに届く）。
     * 所長を必ず入れるのは、申請用アカウントがアドレスを持たないことがあるため。
     */
    public static function notifyPostOrderNoteUpdated(Order $order, User $updater): void
    {
        $recipients = self::withEmail(
            $order->office->managers()->get()
                ->push($order->requester)
                ->concat(self::generalAffairs())
        )->unique('id');

        self::dispatch($recipients, new PostOrderNoteUpdatedNotification($order, $updater), $order);
    }

    /** 総務ユーザー（有効なもの）全員 */
    private static function generalAffairs(): Collection
    {
        return User::where('role', User::ROLE_GENERAL_AFFAIRS)->where('is_active', true)->get();
    }

    /** 通知先メールアドレスを持つユーザーだけに絞る */
    private static function withEmail(Collection $users): Collection
    {
        return $users->filter(fn (User $user) => filled($user->email))->values();
    }

    /**
     * 通知を送る。メール送信は同期実行なので、宛先が存在しない・SMTPが落ちている等で
     * 例外が飛ぶと承認・申請の操作そのものがエラーになってしまう（DBは既にコミット済み）。
     * 送信失敗は業務を止めずログに残すだけにして、画面はエラーにしない。
     */
    private static function dispatch(Collection $recipients, BaseNotification $notification, Order $order): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, $notification);
        } catch (Throwable $e) {
            Log::error('発注通知メールの送信に失敗しました', [
                'order_id' => $order->id,
                'notification' => $notification::class,
                'recipients' => $recipients->pluck('email')->all(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
