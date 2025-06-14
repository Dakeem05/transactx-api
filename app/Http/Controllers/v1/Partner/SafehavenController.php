<?php

namespace App\Http\Controllers\v1\Partner;

use App\Enums\PartnersEnum;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessSafehavenWebhook;
use App\Models\Settings;
use App\Models\Transaction;
use App\Services\External\SafehavenService;
use App\Services\UserService;
use App\Services\WebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SafehavenController extends Controller
{
    public $userService;
    public $safehavenService;

    public function __construct(
        public WebhookService $webhookService
    ) {
        $this->userService = resolve(UserService::class);
        $this->safehavenService = resolve(SafehavenService::class);
    }


    public function getBanks()
    {
        try {
            $banks = $this->safehavenService->getBanks();

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

            $account = $this->safehavenService->resolveAccount($account_number, $bank_code);

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
            $ipAddress = $request->ip();

            // Log initial receipt
            Log::info('Webhook received!', compact("payload"));

            // Dispatch to queue
            ProcessSafehavenWebhook::dispatch($payload, $ipAddress);

            return response()->json(['message' => 'Webhook queued for processing'], 202);

        } catch (Exception $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all() ?? null
            ]);
            return response()->json(['message' => 'Processing error'], 500);
        }
    }
}
