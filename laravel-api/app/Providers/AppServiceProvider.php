<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('app.api_rate_limit', 120)))
            ->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('auth-register', static fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('password-change', static fn (Request $request): Limit => Limit::perMinute(5)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('checkout', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('payment-create', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('return-create', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('cart-mutation', static fn (Request $request): Limit => Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('payment-webhook', static fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('shipping-webhook', static fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
    }
}
