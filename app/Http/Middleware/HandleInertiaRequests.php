<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Public storefront/checkout routes aren't behind the `auth`
        // middleware, but if an admin happens to be signed in on the same
        // browser, $request->user() would still resolve to them — leaking
        // their name/email/2FA status into a page anyone can view-source.
        // Only expose the authenticated user on routes that actually
        // require login.
        $requiresAuth = $request->route()
            && in_array('auth', $request->route()->gatherMiddleware(), true);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $requiresAuth ? $request->user() : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
