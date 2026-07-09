<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentTrackingAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shipment $shipment) {}

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
        $order = $this->shipment->order;

        return (new MailMessage)
            ->subject("Nomor Resi Pesanan {$order->order_number} Tersedia")
            ->greeting('Pesananmu sudah dikirim!')
            ->line("Pesanan {$order->order_number} telah dikirim menggunakan {$this->shipment->courier} ({$this->shipment->service}).")
            ->line("Nomor Resi: {$this->shipment->tracking_number}")
            ->line('Gunakan nomor resi ini untuk melacak posisi paketmu di situs kurir terkait.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $order = $this->shipment->order;

        return "Pesananmu sudah dikirim!\n\n"
            ."Pesanan {$order->order_number} telah dikirim menggunakan {$this->shipment->courier} ({$this->shipment->service}).\n\n"
            ."Nomor Resi: *{$this->shipment->tracking_number}*\n\n"
            .'Gunakan nomor resi ini untuk melacak posisi paketmu di situs kurir terkait.';
    }
}
