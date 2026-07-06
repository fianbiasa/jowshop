<?php

namespace App\Concerns;

use Illuminate\Support\Facades\View;

trait SharesSeoMeta
{
    /**
     * Share title/description/image/canonical-url for a page as a Blade
     * view variable, rendered directly into the raw HTML by
     * resources/views/app.blade.php. This deliberately bypasses Inertia's
     * client-side <Head> management: link-preview crawlers (Facebook,
     * WhatsApp, Twitter) fetch raw HTML and never execute JavaScript, and
     * this app has no SSR running, so anything rendered only via Inertia's
     * <Head> would be invisible to them.
     */
    private function shareSeoMeta(string $title, string $description, ?string $image = null): void
    {
        View::share('seo', [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            // No query string — avoids treating every ?fbclid=/?utm_= permutation of the same page as a distinct canonical URL.
            'url' => request()->url(),
        ]);
    }
}
