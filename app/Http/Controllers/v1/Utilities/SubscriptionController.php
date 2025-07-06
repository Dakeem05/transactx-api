<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Subscription\UpgradeUserSubscriptionRequest;
use App\Http\Requests\User\Subscription\UserSubscriptionRequest;
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

    public function fetchSubscriptionMethods(): JsonResponse
    {
        try {
            $response = $this->subscriptionService->fetchSubscriptionMethods(Auth::user());

            return TransactX::response(true, 'Subscription methods fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('FETCH SUBSCRIPTION METHODS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('FETCH SUBSCRIPTION METHODS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch subscription methods', 500);
        }
    }

    public function fetchUserSubscription(): JsonResponse
    {
        try {
            $response = $this->subscriptionService->fetchUserSubscription(Auth::user());

            return TransactX::response(true, 'User subscription fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('FETCH USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('FETCH USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch user subscription', 500);
        }
    }

    public function subscribe(UserSubscriptionRequest $request): JsonResponse
    {
        try {
            $this->subscriptionService->upgradeUserSubscription(Auth::user(), $request->validated());

            return TransactX::response(true, 'User subscription proccessed successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to proccess user subscription', 500);
        }
    }

    public function upgradeUserSubscription(UpgradeUserSubscriptionRequest $request): JsonResponse
    {
        try {
            $this->subscriptionService->upgradeUserSubscription(Auth::user(), $request->validated());

            return TransactX::response(true, 'User subscription upgraded successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPGRADE USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPGRADE USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to upgrade user subscription', 500);
        }
    }

    public function cancelSubscription(): JsonResponse
    {
        try {
            $this->subscriptionService->cancelSubscription(Auth::user());

            return TransactX::response(true, 'User subscription cancelled successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CANCEL USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CANCEL USER SUBSCRIPTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to cancel user subscription', 500);
        }
    }
}