<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Http\MonoHttpMacro;
use Exception; 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class MonoHttpMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Http::macro('talkToMono', function (string $url, string $method = 'GET', array $data = [], array $headers = [], array $query = []) {
            // dd("MonoHttpMacroServiceProvider + $url");
            return MonoHttpMacro::makeApiCall($url, $method, $data, $headers, $query);
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
