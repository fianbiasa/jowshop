<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pembayaran Berhasil — Pesanan {$this->order->order_number}")
            ->greeting('Pembayaranmu sudah kami terima!')
            ->line("Pesanan {$this->order->order_number} sedang kami siapkan untuk dikirim.")
            ->line('Kamu akan menerima email lagi berisi nomor resi begitu paketnya dikirim.');
    }
}
