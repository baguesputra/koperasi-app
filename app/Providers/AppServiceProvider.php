<?php

namespace App\Providers;

use App\Services\SSO\PerusahaanProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
        Socialite::extend('perusahaan', function ($app) {
            $config = $app['config']['services.sso'];

            return Socialite::buildProvider(PerusahaanProvider::class, $config);
        });

        RateLimiter::for('sso-callback', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return redirect()->route('login')->with('error', 'Terlalu banyak percobaan. Coba lagi nanti.');
            });
        });
    }
}
