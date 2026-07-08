<?php

namespace App\Http\Middleware;

use App\Models\CdnSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ApplyCdnSettings
{
    /**
     * When a Bunny Pull Zone is configured, point every generated asset URL
     * (built CSS/JS via Vite, and files on the `public` disk — branding
     * logo, product thumbnails) at the CDN instead of this origin. Must run
     * before HandleInertiaRequests, since that middleware already resolves
     * the branding logo URL.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $setting = CdnSetting::query()->first();

        if ($setting?->isConfigured()) {
            $cdnUrl = rtrim($setting->pull_zone_url, '/');

            config(['filesystems.disks.public.url' => $cdnUrl.'/storage']);
            URL::useAssetOrigin($cdnUrl);
        }

        return $next($request);
    }
}
