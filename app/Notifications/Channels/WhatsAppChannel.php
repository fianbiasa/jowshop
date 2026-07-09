<?php

namespace App\Notifications\Channels;

use App\Models\WhatsAppSetting;
use App\Services\StarsenderClient;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(public StarsenderClient $starsender) {}

    /**
     * Send the notification via Starsender when WhatsApp is configured and
     * the notifiable has a phone number; otherwise skip silently so email
     * delivery keeps working without a WhatsApp setup.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $settings = WhatsAppSetting::query()->where('is_active', true)->first();

        if ($settings === null || ! method_exists($notifiable, 'routeNotificationFor')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! is_string($to) || $to === '' || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $this->starsender->sendText($settings, $to, $notification->toWhatsApp($notifiable));
    }
}
