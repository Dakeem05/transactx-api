<?php

namespace App\Providers;

use App\Services\External\PaystackService;
use Illuminate\Support\ServiceProvider;


class PaystackServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaystackService::class, function () {
            return new PaystackService(self::resolveBaseurl());
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
        return config('services.paystack.base_url');
    }
}
