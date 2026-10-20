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
        // Laravel Cloud is only reachable through Cloudflare, which sets CF-Connecting-IP to the player's address.
        RateLimiter::for('guesses', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->header('CF-Connecting-IP') ?? $request->ip()));
    }
}
