<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItemDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DigitalDeliveryAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, OrderItemDelivery>  $deliveries
     */
    public function __construct(public Order $order, public Collection $deliveries) {}

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
        $mail = (new MailMessage)
            ->subject("File Digital Pesanan {$this->order->order_number} Siap Diunduh")
            ->greeting('Terima kasih atas pembelianmu!')
            ->line("Pesanan {$this->order->order_number} telah dibayar dan file digitalmu sudah siap diunduh.");

        foreach ($this->deliveries as $delivery) {
            $mail->line("**{$delivery->orderItem->product->name}**");
            $mail->line(route('digital-download.show', $delivery->download_token));

            if ($delivery->license_key !== null) {
                $mail->line("Kode Lisensi: {$delivery->license_key}");
            }
        }

        return $mail
            ->line('Tautan unduhan berlaku selama 30 hari sejak email ini dikirim.');
    }
}
