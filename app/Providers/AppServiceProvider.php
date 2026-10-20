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
        // On Laravel Cloud the framework trusts every proxy, so $request->ip() is the leftmost X-Forwarded-For entry,
        // which a client can set. Cloudflare, in front of every Cloud request, overwrites CF-Connecting-IP instead.
        RateLimiter::for('guesses', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->header('CF-Connecting-IP') ?? $request->ip()));
    }
}
