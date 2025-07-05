<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SubscriptionController extends Controller
{
    /**
     * Create a new SubscriptionController instance.
     */
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) 
    {
    }

    public function fetchSubscriptionModels(): JsonResponse
    {
        try {
            $response = $this->subscriptionService->fetchSubscriptionModels(Auth::user());

            return TransactX::response(true, 'Subscription models successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('FETCH SUBSCRIPTION MODELS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('FETCH SUBSCRIPTION MODELS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch subscription models', 500);
        }
    }

}