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

            return TransactX::response(true, 'Subscription models retrieved successfully', 200, [
                'models' => $models
            ]);
        } catch (Exception $e) {
            Log::error('USER: LIST SUBSCRIPTION MODELS: Error Encountered: ' . $e->getMessage());
            
            return TransactX::response(false, $e->getMessage(), 500);
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
            
            return TransactX::response(true, 'Subscription model retrieved successfully', 200, [
                'model' => $model
            ]);
        } catch (ModelNotFoundException | NotFoundHttpException $e) {
            Log::error('USER: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Cannot find Subscription Model', 404);
        } catch (Exception $e) {
            Log::error('USER: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
