<?php

namespace App\Providers;

use App\Services\SSO\PerusahaanProvider;
use App\Services\Wa\Gateway\BaileysGateway;
use App\Services\Wa\Gateway\WaGateway;
use App\Services\Wa\Gateway\WahaGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WaGateway::class, function () {
            $gateway = config('services.wa.gateway', 'baileys');

            return match ($gateway) {
                'baileys' => new BaileysGateway,
                'waha' => new WahaGateway,
                default => throw new InvalidArgumentException("Gateway WhatsApp tidak dikenal: {$gateway}."),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('perusahaan', PerusahaanProvider::class);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('sso-callback', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return redirect()->route('login')->with('error', 'Terlalu banyak percobaan. Coba lagi nanti.');
            });
        });

        PreventRequestForgery::except([
            '/auth/sso/callback',
            '/auth/sso/slo',
        ]);
    }
}
