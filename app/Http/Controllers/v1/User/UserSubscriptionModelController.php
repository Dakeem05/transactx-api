<?php

namespace App\Http\Controllers\v1\User;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\Business\SubscriptionModelService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserSubscriptionModelController extends Controller
{

    /**
     * Create a new UserSubscriptionModelController instance.
     *
     */
    public function __construct(
        protected SubscriptionModelService $subscriptionModelService
    ) {
    }


    /**
     * Return all the subscription models.
     */
    public function index(): JsonResponse
    {
        try {
            $models = $this->subscriptionModelService->getModels();

            return TransactX::response([
                'message' => 'Subscription models retrieved successfully',
                'models' => $models
            ], 200);
        } catch (Exception $e) {
            Log::error('USER: LIST SUBSCRIPTION MODELS: Error Encountered: ' . $e->getMessage());

            return TransactX::response(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Return a single subscription model.
     * @param string $id
     */
    public function show(string $id): JsonResponse
    {
        try {
            $model = $this->subscriptionModelService->getById($id);

            return TransactX::response([
                'message' => 'Subscription model retrieved successfully',
                'model' => $model
            ], 200);
        } catch (ModelNotFoundException | NotFoundHttpException $e) {
            Log::error('USER: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Cannot find Subscription Model'], 404);
        } catch (Exception $e) {
            Log::error('USER: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 500);
        }
    }
}
