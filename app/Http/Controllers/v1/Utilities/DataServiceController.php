<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Services\BuyDataServiceRequest;
use App\Http\Requests\User\Services\RetrieveCategoryProductRequest;
use App\Models\Transaction;
use App\Services\Utilities\DataService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DataServiceController extends Controller
{
    public function __construct(
        protected DataService $dataService,
    ) {
        $this->middleware('user.is.main.account')->except(['getNetworks']);
    }

    public function getNetworks()
    {
        try {
            $networks = $this->dataService->getNetworks();
            return TransactX::response(true, 'Networks fetched successfully', 200, $networks);
        } catch (InvalidArgumentException $e) {
            Log::error('Get network: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get network: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get networks', 500);
        }
    }

    public function getPlans(RetrieveCategoryProductRequest $request)
    {
        try {
            $plans = $this->dataService->getPlans($request->validated());
            return TransactX::response(true, 'Data plans fetched successfully', 200, $plans);
        } catch (InvalidArgumentException $e) {
            Log::error('Get plans: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get plans: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get data plans', 500);
        }
    }

    public function buyData(BuydataServiceRequest $request)
    {
        try {
            $this->dataService->buyData($request->validated(), Auth::user());
            return TransactX::response(true, 'Data bought successfully', 200, );
        } catch (InvalidArgumentException $e) {
            Log::error('Buy data: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy data: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy data', 500);
        }
    }

}