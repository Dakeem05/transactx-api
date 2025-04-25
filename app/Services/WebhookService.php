<?php

namespace App\Services;

use App\Models\Webhook;
use Exception;

class WebhookService
{
    public function recordIncomingWebhook(string $provider, ?array $requestPayload, ?array $responsePayload, int $statusCode, string $ipAddress)
    {
        try {
            return Webhook::create([
                'provider' => $provider,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'response_http_code' => $statusCode,
                'ip_address' => $ipAddress,
                'type' => 'received',
            ]);
        } catch (Exception $e) {
            logger("Error Occurred when saving webhook" . $e->getMessage());
        }
    }
}
