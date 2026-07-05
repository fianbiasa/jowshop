<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmed extends Notification implements ShouldQueue
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
        $this->order->loadMissing('items.product');

        $mail = (new MailMessage)
            ->subject("Pesanan {$this->order->order_number} Diterima")
            ->greeting('Terima kasih atas pesananmu!')
            ->line("Kami sudah menerima pesanan {$this->order->order_number}, berikut rinciannya:");

        foreach ($this->order->items as $item) {
            $mail->line("{$item->quantity}x {$item->product->name} — ".$this->formatPrice($item->unit_price));
        }

        return $mail
            ->line('**Total: '.$this->formatPrice($this->order->total).'**')
            ->action('Selesaikan Pembayaran', $this->order->resumePaymentUrl())
            ->line('Pesanan akan diproses setelah pembayaran diterima.');
    }

    private function formatPrice(string $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}
