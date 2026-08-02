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
        RateLimiter::for('doctor-access-redeem', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->user_id ?: $request->ip());
        });

        RateLimiter::for('child-login', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('verify-email', function (Request $request) {
            return Limit::perMinute(10)->by($request->input('email', $request->ip()));
        });

        RateLimiter::for('resend-verification', function (Request $request) {
            return Limit::perMinute(3)->by($request->input('email', $request->ip()));
        });
    }
}
