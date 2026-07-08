<?php

use App\Console\Commands\SendPaymentReminders;
use App\Http\Middleware\ApplyCdnSettings;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->validateCsrfTokens(except: ['webhooks/duitku']);

        $middleware->web(append: [
            ApplyCdnSettings::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendPaymentReminders::class)->hourly();

        // Processes queued jobs (Meta CAPI events, etc.) via the same cron
        // entry the scheduler itself needs — no Supervisor/persistent
        // process required, so this works on shared hosting too, not just
        // servers with root access. Exits after each minute's worth of
        // work (or 55s, whichever comes first) so it never overlaps with
        // the next run.
        $schedule->command('queue:work --stop-when-empty --max-time=55')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
