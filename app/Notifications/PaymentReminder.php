<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $reminderNumber  1, 2, or 3 — the reminder copy escalates
     *                               in urgency as this goes up (sent roughly
     *                               24h/48h/72h after the order is placed).
     */
    public function __construct(public Order $order, public int $reminderNumber) {}

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
        $amount = 'Rp'.number_format((float) $this->order->total, 0, ',', '.');

        return (new MailMessage)
            ->subject($this->subject())
            ->greeting($this->greeting())
            ->line("Pesanan {$this->order->order_number} senilai **{$amount}** masih menunggu pembayaran.")
            ->line($this->urgencyLine())
            ->action('Selesaikan Pembayaran', $this->order->resumePaymentUrl());
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $amount = 'Rp'.number_format((float) $this->order->total, 0, ',', '.');

        return $this->greeting()."\n\n"
            ."Pesanan {$this->order->order_number} senilai *{$amount}* masih menunggu pembayaran.\n\n"
            .$this->urgencyLine()."\n\n"
            ."Selesaikan pembayaran di sini:\n"
            .$this->order->resumePaymentUrl();
    }

    private function subject(): string
    {
        return match ($this->reminderNumber) {
            1 => "Pesanan {$this->order->order_number} Menunggu Pembayaran",
            2 => "Jangan Sampai Kehabisan — Pesanan {$this->order->order_number}",
            default => "Pengingat Terakhir — Pesanan {$this->order->order_number} Akan Hangus",
        };
    }

    private function greeting(): string
    {
        return match ($this->reminderNumber) {
            1 => 'Masih ingin melanjutkan?',
            2 => 'Stok terbatas, lho!',
            default => 'Ini kesempatan terakhirmu!',
        };
    }

    private function urgencyLine(): string
    {
        return match ($this->reminderNumber) {
            1 => 'Selesaikan pembayaran sekarang supaya pesananmu bisa langsung kami proses.',
            2 => 'Stok produk ini terbatas dan banyak yang mengantre — amankan pesananmu sebelum kehabisan.',
            default => 'Ini pengingat terakhir yang akan kamu terima untuk pesanan ini. Selesaikan pembayaran sekarang sebelum kesempatan ini hilang.',
        };
    }
}
