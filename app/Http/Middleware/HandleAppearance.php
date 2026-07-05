<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Route name prefixes/names for customer-facing pages (salespage,
     * checkout, order lookup, digital download). These carry the admin's
     * deliberately chosen salespage style/branding and must render
     * identically for every visitor — never following a visitor's device
     * dark-mode setting, or an admin's own dashboard appearance cookie
     * bleeding through when they preview the page in the same browser.
     *
     * @var array<int, string>
     */
    private const CUSTOMER_FACING_ROUTES = [
        'public.*',
        'order-lookup.*',
        'digital-download.*',
        'order.resume-payment',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        $appearance = $routeName !== null && Str::is(self::CUSTOMER_FACING_ROUTES, $routeName)
            ? 'light'
            : ($request->cookie('appearance') ?? 'system');

        View::share('appearance', $appearance);

        return $next($request);
    }
}
