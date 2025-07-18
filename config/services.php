<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | These configuration options are used by the payment gateway
    |
    */
    'paystack' => [
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],
    'flutterwave' => [
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        'webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH')
    ],
    'safehaven' => [
        'mode' => env('SAFEHAVEN_MODE'),
        'live_url' => env('SAFEHAVEN_LIVE_URL'),
        'sandbox_url' => env('SAFEHAVEN_SANDBOX_URL'),
        'client_id' => env('SAFEHAVEN_CLIENT_ID'),
        'sandbox_client_id' => env('SAFEHAVEN_SANDBOX_CLIENT_ID'),
        'client_assertion' => env('SAFEHAVEN_CLIENT_ASSERTION'),
        'sandbox_client_assertion' => env('SAFEHAVEN_SANDBOX_CLIENT_ASSERTION'),
        'account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
    ],
    'mono' => [
        'mode' => env('MONO_MODE'),
        'url' => env('MONO_URL'),
        'mono_sandbox_public_key' => env('MONO_SANDBOX_PUBLIC_KEY'),
        'mono_sandbox_secret_key' => env('MONO_SANDBOX_SECRET_KEY'),
        'mono_live_public_key' => env('MONO_LIVE_PUBLIC_KEY'),
        'mono_live_secret_key' => env('MONO_LIVE_SECRET_KEY'),
        'mono_test_webhook_hash' => env('MONO_TEST_WEBHOOK_HASH'),
        'mono_live_webhook_hash' => env('MONO_LIVE_WEBHOOK_HASH'),
    ],
];
