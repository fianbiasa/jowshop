<?php

namespace App\Services;

use App\Models\WhatsAppSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class StarsenderClient
{
    /**
     * Send a plain-text WhatsApp message through the Starsender send API.
     *
     * @throws RequestException when Starsender rejects the request, so the
     *                          queued notification job can be retried.
     */
    public function sendText(WhatsAppSetting $settings, string $to, string $body): void
    {
        Http::withHeaders(['Authorization' => $settings->api_key])
            ->asJson()
            ->post('https://api.starsender.online/api/send', [
                'messageType' => 'text',
                'to' => $to,
                'body' => $body,
            ])
            ->throw();
    }
}
