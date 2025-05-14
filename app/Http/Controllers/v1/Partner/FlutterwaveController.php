<?php

namespace App\Http\Controllers\v1\Partner;

use App\Enums\PartnersEnum;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\Transaction;
use App\Services\External\FlutterwaveService;
use App\Services\UserService;
use App\Services\WebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FlutterwaveController extends Controller
{
    public $userService;
    public $flutterwaveService;

    public function __construct(
        public WebhookService $webhookService
    ) {
        $this->userService = resolve(UserService::class);
        $this->flutterwaveService = resolve(FlutterwaveService::class);
    }


    public function getBanks()
    {
        try {
            $banks = $this->flutterwaveService->getBanks();

            return TransactX::response(true, 'Banks retrieved successfully.', 200, (object)['banks' => $banks]);
        } catch (Exception $e) {
            Log::error('get Banks: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }


    public function resolveAccount(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_number' => ['bail', 'required', 'string'],
                'bank_code' => ['bail', 'required', 'string'],
            ]);

            if ($validator->fails()) {
                return TransactX::response(false,  'Validation error', 422, $validator->errors());
            }

            $account_number = $request->account_number;
            $bank_code = $request->bank_code;

            $account = $this->flutterwaveService->resolveAccount($account_number, $bank_code);

            return TransactX::response(true, 'Account resolved successfully!', 200, (object)['account' => $account['data']]);
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
            
            // only a post with flutterwave signature header gets our attention
            if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !isset($_SERVER['HTTP_VERIF_HASH'])) {
                throw new Exception('Invalid signature');
            }

            //get Flutterwave webhook hash
            $secret_webhook_hash = config('services.flutterwave.webhook_hash');

            // validate event do all at once to avoid timing attack
            if ($_SERVER['HTTP_VERIF_HASH'] != $secret_webhook_hash) {
                throw new Exception('Invalid signature');
            }

            $responseData = ['message' => 'Webhook received!'];
            $ipAddress = $request->ip();

            $this->webhookService->recordIncomingWebhook(PartnersEnum::FLUTTERWAVE->value, $payload, $responseData, Response::HTTP_OK, $ipAddress);

            $event_type = $payload['event'];

            // Payout subaccount funding webhook
            if (in_array($event_type, ['transfer.completed']) && $payload['data']['debit_currency'] === 'PSA' && $payload['data']['status'] === 'SUCCESSFUL') {
                $external_transaction_reference = $payload['data']['reference'];
                $account_number = $payload['data']['account_number'];
                $amount = $payload['data']['amount'];
                $currency = Settings::where('name', 'currency')->first()->value;
                event(new WalletTransactionReceived($account_number, $amount, $currency, $external_transaction_reference));
                
                $sender_transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)->where('status', 'PENDING')->orWhere('status', 'PROCESSING')->with(['wallet', 'user'])->first();

                if ($sender_transaction) {
                    event(new TransferSuccessful($sender_transaction, $account_number, Settings::where('name', 'currency')->first()->value, $payload['data']['fullname']));
                }
                return;
            }

            // Successful transfers webhook
            if (in_array($event_type, ['transfer.completed']) && $payload['data']['debit_currency'] === Settings::where('name', 'currency')->first()->value && $payload['data']['status'] === 'SUCCESSFUL') {
                $external_transaction_reference = $payload['data']['reference'];
                $account_number = $payload['data']['metadata']['account_number'];
                
                $sender_transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)->where('status', 'PENDING')->orWhere('status', 'PROCESSING')->with(['wallet', 'user'])->first();

                if ($sender_transaction) {
                    event(new TransferSuccessful($sender_transaction, $account_number, Settings::where('name', 'currency')->first()->value, $payload['data']['fullname']));
                }
                return;
            }
            
            // Failed transfers webhook
            if (in_array($event_type, ['transfer.completed']) && $payload['data']['debit_currency'] === Settings::where('name', 'currency')->first()->value && $payload['data']['status'] === 'FAILED') {
                $external_transaction_reference = $payload['data']['reference'];
                $account_number = $payload['data']['metadata']['account_number'];
                
                $sender_transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)->where('status', 'PENDING')->orWhere('status', 'PROCESSING')->first();
                
                if ($sender_transaction) {
                    event(new TransferFailed($sender_transaction, $payload['data']['fullname']));
                }
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
