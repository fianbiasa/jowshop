<?php

namespace App\Services;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class VisitorIdentifier
{
    public const COOKIE_NAME = 'visitor_uuid';

    /**
     * Identify the current visitor via a long-lived first-party cookie,
     * creating a new Visitor record (and capturing UTM/referrer/landing
     * data) the first time they are seen.
     */
    public function identify(Request $request): Visitor
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        $uuid = is_string($cookieValue) ? $cookieValue : null;

        if ($uuid !== null) {
            $visitor = Visitor::query()->where('uuid', $uuid)->first();

            if ($visitor !== null) {
                $visitor->update(['last_seen_at' => now()]);

                return $visitor;
            }
        }

        $uuid ??= (string) Str::uuid();

        $visitor = Visitor::query()->create([
            'uuid' => $uuid,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request),
            'referrer' => $request->header('referer'),
            'landing_url' => $request->fullUrl(),
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc'),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        Cookie::queue(Cookie::make(self::COOKIE_NAME, $uuid, 60 * 24 * 365 * 5));

        return $visitor;
    }

    private function detectDeviceType(Request $request): string
    {
        $userAgent = strtolower((string) $request->userAgent());

        if (str_contains($userAgent, 'ipad') || str_contains($userAgent, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
