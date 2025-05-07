<?php

namespace App\Http\Controllers\v1\Admin;

use App\Enums\Subscription\ModelNameEnum;
use App\Helpers\TransactX;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Business\SubscriptionModelService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminSubscriptionModelController extends Controller
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

            return TransactX::response($models, 200);
        } catch (Exception $e) {
            Log::error('ADMIN: LIST SUBSCRIPTION MODELS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }


    /**
     * Creates a new subscription model.
     * @param Request $request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'in:' . implode(',', ModelNameEnum::toArray())],
            'features' => ['required', 'array'],
            'has_discount' => ['required', 'boolean'],
            'discount' => ['required', 'numeric'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            return TransactX::response(false,  'Validation error', 422, $validator->errors());
        }

        $payload = $request->validated();

        try {
            $model = $this->subscriptionModelService->createModel(
                $payload['name'],
                $payload['features'],
                $payload['has_discount'],
                $payload['discount'],
                $payload['amount'],
            );

            return TransactX::response($model, 201);
        } catch (Exception $e) {
            Log::error('ADMIN: CREATE SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
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

            return TransactX::response($model, 200);
        } catch (ModelNotFoundException | NotFoundHttpException $e) {
            Log::error('ADMIN: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Cannot find Subscription Model'], 404);
        } catch (Exception $e) {
            Log::error('ADMIN: SHOW SUBSCRIPTION MODEL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
