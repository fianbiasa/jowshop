<?php

namespace App\Services;

use App\Models\MetaCapiSetting;
use Illuminate\Support\Facades\Http;

class MetaConversionsApiClient
{
    /**
     * Send a single event to Meta's Conversions API (Graph API `/events`
     * endpoint). Returns the raw outcome instead of throwing, since the
     * caller (a queued job) needs the response code/body either way for
     * its own delivery log.
     *
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     * @return array{successful: bool, status: int, body: string}
     */
    public function send(
        MetaCapiSetting $settings,
        string $pixelId,
        string $eventName,
        string $eventId,
        array $userData,
        array $customData = [],
    ): array {
        $event = [
            'event_name' => $eventName,
            'event_time' => now()->timestamp,
            'event_id' => $eventId,
            'action_source' => 'website',
            'user_data' => $userData,
        ];

        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        $payload = ['data' => [$event], 'access_token' => $settings->access_token];

        if ($settings->test_event_code !== null) {
            $payload['test_event_code'] = $settings->test_event_code;
        }

        $response = Http::asJson()->post("https://graph.facebook.com/v19.0/{$pixelId}/events", $payload);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => (string) $response->body(),
        ];
    }
}
