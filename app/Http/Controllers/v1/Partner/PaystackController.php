<?php

namespace App\Http\Controllers\v1\Partner;

use App\Enums\PartnersEnum;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\WebhookService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    public $userService;

    public function __construct(
        public WebhookService $webhookService
    ) {
        $this->userService = resolve(UserService::class);
    }

    public function handleWebhook(Request $request)
    {
        try {

            $payload = $request->all();
            Log::info('Webhook received!', compact("payload"));
            Log::info('Request headers', $request->header());

            // only a post with paystack signature header gets our attention
            if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
                throw new Exception('Invalid signature');
            }

            // Retrieve the request's body
            $input = file_get_contents('php://input');

            //get PAYSTACK_SECRET_KEY from environment variables
            $secret_key = env('PAYSTACK_SECRET_KEY');

            // Define PAYSTACK_SECRET_KEY
            define('PAYSTACK_SECRET_KEY', $secret_key);

            // validate event do all at once to avoid timing attack
            if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] != hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY)) {
                throw new Exception('Invalid signature');
            }

            $responseData = ['message' => 'Webhook received!'];
            $ipAddress = $request->ip();

            $this->webhookService->recordIncomingWebhook(PartnersEnum::PAYSTACK->value, $payload, $responseData, Response::HTTP_OK, $ipAddress);

            $event_type = $payload['event'];

            if (in_array($event_type, ['customeridentification.success', 'customeridentification.failed'])) {
                $status = explode('.', $event_type)[1];
                $this->userService->processCustomerIdentification($status, $payload);
            }


            return response()->json($responseData, Response::HTTP_OK);

            // 
        } catch (\Exception $e) {
            Log::error('Paystack webhook Error: ', ["error" => $e->getMessage()]);
            return response()->json(['message' => 'Error occurred'], 500);
        }
    }
}
