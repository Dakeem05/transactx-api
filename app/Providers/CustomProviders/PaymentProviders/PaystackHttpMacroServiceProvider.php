<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Http\PaystackHttpMacro;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class PaystackHttpMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Http::macro('talkToPaystack', function (string $url, string $method = 'GET', array $data = []) {
            return PaystackHttpMacro::makeApiCall($url, $method, $data);
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
}
