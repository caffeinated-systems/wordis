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
        // Laravel Cloud already passes the player's real IP through Cloudflare, so no trusted proxies are needed.
        RateLimiter::for('guesses', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
    }
}
