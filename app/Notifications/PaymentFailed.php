<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
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
        return ['mail', WhatsAppChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pembayaran Gagal — Pesanan {$this->order->order_number}")
            ->greeting('Pembayaranmu belum berhasil.')
            ->line("Pembayaran untuk pesanan {$this->order->order_number} gagal diproses. Ini bisa terjadi karena saldo tidak cukup, batas waktu habis, atau gangguan sementara.")
            ->action('Coba Bayar Lagi', $this->order->resumePaymentUrl())
            ->line('Pesananmu masih tersimpan, jadi kamu bisa langsung coba lagi kapan saja.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        return "Pembayaranmu belum berhasil.\n\n"
            ."Pembayaran untuk pesanan {$this->order->order_number} gagal diproses. Ini bisa terjadi karena saldo tidak cukup, batas waktu habis, atau gangguan sementara.\n\n"
            ."Coba bayar lagi lewat tautan berikut:\n"
            .$this->order->resumePaymentUrl()."\n\n"
            .'Pesananmu masih tersimpan, jadi kamu bisa langsung coba lagi kapan saja.';
    }
}
