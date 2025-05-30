<?php

namespace App\Http;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveHttpMacro
{
    /**
     * Make an API call using Laravel's HTTP client.
     *
     * @param string $url    The URL for the API endpoint.
     * @param string $method The HTTP method (GET, POST, PUT, etc.).
     * @param array  $data   The data to send in the request body.
     *
     * @return array|mixed
     */
    public static function makeApiCall(string $url, string $method = 'GET', array $data = [])
    {
        try {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->{$method}($url, $data);

            if ($response->failed()) {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseHeaders = $response->headers();

                throw new Exception("Flutterwave API request failed with status code $statusCode. Response body: $responseBody, Headers: " . json_encode($responseHeaders));
            }

            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
