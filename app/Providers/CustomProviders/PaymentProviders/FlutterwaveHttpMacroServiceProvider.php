<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Http\FlutterwaveHttpMacro;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class FlutterwaveHttpMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Http::macro('talkToFlutterwave', function (string $url, string $method = 'GET', array $data = []) {
            return FlutterwaveHttpMacro::makeApiCall($url, $method, $data);
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
