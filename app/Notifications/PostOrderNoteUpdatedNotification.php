<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 発注者メモが更新されたことを知らせるメール。
 *
 * メモは発注後（発注済）に総務・管理者が書くもので、業者から言われたこと等が入る。
 * 申請した営業所側にも知らせたいので、所長・申請者・総務へ送る（宛先は OrderNotifier）。
 */
class PostOrderNoteUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public User $updater, // メモを更新した人
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        $message = (new MailMessage)
            ->subject("【資材発注】発注者メモが更新されました（#{$order->id}）")
            ->greeting("{$notifiable->name} さん")
            ->line("発注申請 #{$order->id} の発注者メモが更新されました（{$this->updater->name}）。")
            ->line("営業所：{$order->office->name}")
            ->line("業者：{$order->supplier?->name}")
            ->line("申請者：{$order->requester_name}");

        // 空にされた場合もあるので、その旨が分かるように出し分ける
        $message->line(filled($order->post_order_note)
            ? "メモの内容：\n{$order->post_order_note}"
            : '※ メモの内容は削除されました。');

        return $message
            ->action('申請内容を確認する', route('orders.show', $order))
            ->line('内容をご確認ください。');
    }
}
