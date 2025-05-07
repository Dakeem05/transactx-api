<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Services\External\FlutterwaveService;
use Illuminate\Support\ServiceProvider;


class FlutterwaveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FlutterwaveService::class, function () {
            return new FlutterwaveService(self::resolveBaseurl());
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
    private function resolveBaseurl(): string
    {
        return config('services.flutterwave.base_url');
    }
}
