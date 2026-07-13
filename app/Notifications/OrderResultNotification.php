<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 申請者へ最終結果（発注済 or 却下）を通知するメール。
 */
class OrderResultNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        if ($order->isRejected()) {
            return (new MailMessage)
                ->subject("【資材発注】申請が却下されました（#{$order->id}）")
                ->greeting("{$notifiable->name} 様")
                ->line("発注申請 #{$order->id} は却下されました。")
                ->line("却下理由：{$order->reject_reason}")
                ->action('申請内容を確認する', route('orders.show', $order))
                ->line('ご不明な点は担当者までお問い合わせください。');
        }

        // 発注済
        $message = (new MailMessage)
            ->subject("【資材発注】発注が確定しました（#{$order->id}）")
            ->greeting("{$notifiable->name} 様")
            ->line("発注申請 #{$order->id} が承認され、発注が確定しました。");

        if ($order->is_special_approval) {
            $message->line('※ 総務による特例承認で処理されました。');
        }

        return $message
            ->line("点数：{$order->items->count()} 点 / 参考合計：¥" . number_format($order->totalPrice()))
            ->action('申請内容を確認する', route('orders.show', $order))
            ->line('ご利用ありがとうございます。');
    }
}
