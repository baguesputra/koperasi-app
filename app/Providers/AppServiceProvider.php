<?php

namespace App\Providers;

use App\Notifications\Channels\BaileysChannel;
use App\Services\SSO\PerusahaanProvider;
use Illuminate\Notifications\ChannelManager;
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

        $this->app->extend(ChannelManager::class, function ($manager, $app) {
            $manager->extend('baileys', function () {
                return new BaileysChannel();
            });
            return $manager;
        });
    }
}
