<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Http\SafehavenHttpMacro;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class SafehavenHttpMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Http::macro('talkToSafehaven', function (string $url, string $method = 'GET', array $data = []) {
            return SafehavenHttpMacro::makeApiCall($url, SafehavenServiceProvider::resolveBaseurl(), $method, $data);
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
