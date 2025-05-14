<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Enums\PartnersEnum;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Transactions\Payment\ResolveAccountRequest;
use App\Models\Settings;
use App\Services\UserService;
use App\Services\Utilities\PaymentService;
use App\Services\WebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public $userService;

    public function __construct(
        public WebhookService $webhookService,
        // private PaymentService $paymentService
    ) {
        $this->userService = resolve(UserService::class);
    }

    public function getBanks()
    {
        try {
            $paymentService = resolve(PaymentService::class);
            return TransactX::response(true, 'Banks retrieved successfully.', 200, $paymentService->getBanks());
        } catch (Exception $e) {
            Log::error('get Banks: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
    
    public function resolveAccount(ResolveAccountRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $account_number = $request->account_number;
            $bank_code = $request->bank_code;
            
            $paymentService = resolve(PaymentService::class);
            $account = $paymentService->resolveAccount($account_number, $bank_code);

            return TransactX::response(true, 'Account resolved successfully!', 200, $account['data']);
        } catch (Exception $e) {
            Log::error('Resolve Account: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
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

            // Customer Identification Webhook
            if (in_array($event_type, ['customeridentification.success', 'customeridentification.failed'])) {
                $status = explode('.', $event_type)[1];
                $this->userService->processCustomerIdentification($status, $payload);
                return;
            }

            // Virtual Account Collection Webhook
            if (in_array($event_type, ['charge.success']) && $payload['data']['channel'] === 'dedicated_nuban') {
                $external_transaction_reference = $payload['data']['reference'];
                $account_number = $payload['data']['metadata']['receiver_account_number'];
                $amount = floor($payload['data']['amount'] / 100);
                $currency = Settings::where('name', 'currency')->first()->value;
                event(new WalletTransactionReceived($account_number, $amount, $currency, $external_transaction_reference));
                return;
            }

            return response()->json($responseData, Response::HTTP_OK);

            // 
        } catch (Exception $e) {
            Log::error('Paystack Webhook Error: ', ["error" => $e->getMessage()]);
            return response()->json(['message' => 'Error occurred'], 500);
        }
    }
}
