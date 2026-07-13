<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 承認者（所長または総務）へ「確認待ちの申請があります」と通知するメール。
 */
class OrderPendingApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $approverType, // 'manager'（所長へ） or 'affairs'（総務へ）
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $who = $this->approverType === 'manager' ? '所長' : '総務';

        return (new MailMessage)
            ->subject("【資材発注】承認待ちの申請があります（#{$order->id}）")
            ->greeting("{$notifiable->name} 様")
            ->line("あなた（{$who}）の確認待ちの発注申請があります。")
            ->line("申請番号：#{$order->id}")
            ->line("営業所：{$order->office->name}")
            ->line("申請者：{$order->requester->name}")
            ->line("点数：{$order->items->count()} 点 / 参考合計：¥" . number_format($order->totalPrice()))
            ->action('申請内容を確認する', route('orders.show', $order))
            ->line('内容をご確認のうえ、承認または却下をお願いします。');
    }
}
