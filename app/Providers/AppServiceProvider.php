<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Schema::defaultStringLength(191);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Rate limiters for public-facing routes that don't sit behind auth —
     * a normal checkout journey is a handful of deliberate clicks, so these
     * limits are generous enough not to bother real buyers while still
     * capping automated abuse (card-testing bots, order-lookup enumeration,
     * scraping).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-funnel', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('public-download', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('order-lookup', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
