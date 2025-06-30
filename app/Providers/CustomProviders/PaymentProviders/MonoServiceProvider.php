<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Services\External\MonoService;
use Illuminate\Support\ServiceProvider;


class MonoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MonoService::class, function () {
            return new MonoService(self::resolveBaseurl());
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }


    /**
     * Resolve the base URL for the Paystack API from the configuration.
     *
     * @return string The base URL for the Paystack API.
     */
    public static function resolveBaseurl(): string
    {
        return config('services.mono.url');
    }
}
