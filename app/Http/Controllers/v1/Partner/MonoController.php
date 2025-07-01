<?php

namespace App\Http\Controllers\v1\Partner;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMonoWebhook;
use App\Services\External\MonoService;
use App\Services\UserService;
use App\Services\WebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MonoController extends Controller
{
    public $userService;
    public $monoService;

    public function __construct(
        public WebhookService $webhookService
    ) {
        $this->userService = resolve(UserService::class);
        $this->monoService = resolve(MonoService::class);
    }


    // public function getBanks()
    // {
    //     try {
    //         $banks = $this->monoService->getBanks();

    //         return TransactX::response(true, 'Banks retrieved successfully.', 200, (object)['banks' => $banks]);
    //     } catch (Exception $e) {
    //         Log::error('get Banks: Error Encountered: ' . $e->getMessage());
    //         return TransactX::response(false, $e->getMessage(), 500);
    //     }
    // }


    // public function resolveAccount(Request $request): JsonResponse
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'account_number' => ['bail', 'required', 'string'],
    //             'bank_code' => ['bail', 'required', 'string'],
    //         ]);

    //         if ($validator->fails()) {
    //             return TransactX::response(false,  'Validation error', 422, $validator->errors());
    //         }

    //         $account_number = $request->account_number;
    //         $bank_code = $request->bank_code;

    //         $account = $this->monoService->resolveAccount($account_number, $bank_code);

    //         return TransactX::response(true, 'Account resolved successfully!', 200, (object)['account' => $account['data']]);
    //     } catch (Exception $e) {
    //         Log::error('Resolve Account: Error Encountered: ' . $e->getMessage());
    //         return TransactX::response(false, $e->getMessage(), 500);
    //     }
    // }


    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->getContent() ?? null;
            
            if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !isset($_SERVER['HTTP_MONO_WEBHOOK_SECRET'])) {
                throw new Exception('Invalid signature');
            }
            
            $secret_webhook_hash = config('services.mono.mode') == "SANDBOX" ? config('services.mono.mono_test_webhook_hash') : config('services.mono.mono_live_webhook_hash');
            
            if ($_SERVER['HTTP_MONO_WEBHOOK_SECRET'] != $secret_webhook_hash) {
                throw new Exception('Invalid signature');
            }
            
            Log::info('Mono webhook request content 2', ['content' => $payload]);
            $ipAddress = $request->ip();
            
            Log::info('Mono webhook request content 3', ['content' => $payload]);
            Log::info('Mono webhook received! 2', compact("payload"));
            
            ProcessMonoWebhook::dispatch($payload, $ipAddress);
            
            Log::info('Mono webhook request content 4', ['content' => $payload]);
            return response()->json(['message' => 'Webhook queued for processing'], 202);

        } catch (Exception $e) {
            Log::error('Mono webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all() ?? null
            ]);
            return response()->json(['message' => 'Processing error'], 500);
        }
    }

    // public function handleWebhook(Request $request)
    // {
    //     try {
    //         $payload = $request->getContent() ?? null;
    //         Log::info('Mono webhook received!', ['payload' => $payload]);
            
    //         if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !isset($_SERVER['HTTP_MONO_WEBHOOK_SECRET'])) {
    //             throw new Exception('Invalid signature');
    //         }
            
    //         $secret_webhook_hash = config('services.mono.mode') == "SANDBOX" ? config('services.mono.mono_test_webhook_hash') : config('services.mono.mono_live_webhook_hash');
            
    //         if ($_SERVER['HTTP_MONO_WEBHOOK_SECRET'] != $secret_webhook_hash) {
    //             throw new Exception('Invalid signature');
    //         }
            
    //         Log::info('Mono webhook after verification!', ['payload' => $payload]);
    //         $ipAddress = $request->ip();
    //         ProcessMonoWebhook::dispatch($payload, $ipAddress);
    //         return response()->json(['message' => 'Webhook queued for processing'], 202);

    //     } catch (Exception $e) {
    //         Log::error('Mono webhook handling failed', [
    //             'error' => $e->getMessage(),
    //             'payload' => $request->all() ?? null
    //         ]);
    //         return response()->json(['message' => 'Processing error'], 500);
    //     }
    // }
}
