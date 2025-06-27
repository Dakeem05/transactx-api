<?php

namespace App\Http;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafehavenHttpMacro
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
    public static function makeApiCall(string $url, string $base_url, string $method = 'GET', array $data = [])
    {
        try {
            $accessTokens = self::generateAccessToken($base_url);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessTokens['access_token'],
                'ClientID' => $accessTokens['ibs_client_id'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->{$method}($url, $data);
            
            if ($response->failed()) {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseHeaders = $response->headers();
                
                throw new Exception("Safehaven API request failed with status code $statusCode. Response body: $responseBody, Headers: " . json_encode($responseHeaders));
            }
    
            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private static function generateAccessToken (string $base_url) 
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->{'POST'}($base_url . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.safehaven.mode') === 'SANDBOX' ? config('services.safehaven.sandbox_client_id') : config('services.safehaven.client_id'),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => config('services.safehaven.mode') === 'SANDBOX' ? config('services.safehaven.sandbox_client_assertion') : config('services.safehaven.client_assertion')
            ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseHeaders = $response->headers();

                throw new Exception("Safehaven API request failed with status code $statusCode. Response body: $responseBody, Headers: " . json_encode($responseHeaders));
            }
            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
