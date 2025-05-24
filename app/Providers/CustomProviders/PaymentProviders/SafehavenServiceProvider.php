<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Services\External\SafehavenService;
use Illuminate\Support\ServiceProvider;


class SafehavenServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SafehavenService::class, function () {
            return new SafehavenService(self::resolveBaseurl());
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
        if (config('services.safehaven.mode') === 'SANDBOX') {
            return config('services.safehaven.sandbox_url');
        } else {
            return config('services.safehaven.live_url');
        }
    }
}
