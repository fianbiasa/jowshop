<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentInstructions extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $paymentUrl) {}

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
        $amount = 'Rp'.number_format((float) $this->order->total, 0, ',', '.');

        return (new MailMessage)
            ->subject("Cara Bayar Pesanan {$this->order->order_number}")
            ->greeting('Satu langkah lagi!')
            ->line("Selesaikan pembayaran sebesar **{$amount}** untuk pesanan {$this->order->order_number} melalui tautan berikut:")
            ->action('Bayar Sekarang', $this->paymentUrl)
            ->line('Kalau tautan di atas sudah kedaluwarsa, gunakan tautan cadangan ini untuk memilih ulang metode pembayaran:')
            ->line($this->order->resumePaymentUrl());
    }
}
