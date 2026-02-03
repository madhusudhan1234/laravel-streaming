<?php

namespace App\Providers;

use App\Auth\WhitelistUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Auth::provider('whitelist', function ($app, array $config) {
            return new WhitelistUserProvider($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Horizon::auth(function ($request) {
            return $request->user() !== null;
        });
    }
}
