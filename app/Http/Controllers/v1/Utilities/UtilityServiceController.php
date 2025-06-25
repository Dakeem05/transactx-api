<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Services\BuyUtilityServiceRequest;
use App\Http\Requests\User\Services\RetrieveCategoryProductRequest;
use App\Http\Requests\User\Services\VerifyServiceNumberRequest;
use App\Models\Transaction;
use App\Services\Utilities\UtilityService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UtilityServiceController extends Controller
{
    public function __construct(
        protected UtilityService $utilityService,
    ) {
        $this->middleware('user.is.main.account')->except(['getNetworks']);
    }

    public function getCompanies()
    {
        try {
            $companies = $this->utilityService->getCompanies();
            return TransactX::response(true, 'Companies fetched successfully', 200, $companies);
        } catch (InvalidArgumentException $e) {
            Log::error('Get companies: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get companies: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get companies', 500);
        }
    }

    public function verifyNumber(VerifyServiceNumberRequest $request)
    {
        try {
            $data = $this->utilityService->verifyNumber($request->validated());
            return TransactX::response(true, 'Meter number verified successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('Verify utility number: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Verify utility number: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to verify meter number', 500);
        }
    }

    public function buySubscription(BuyUtilityServiceRequest $request)
    {
        try {
            $this->utilityService->buySubscription($request->validated(), Auth::user());
            return TransactX::response(true, 'Utility subscription bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('Buy utility subscription: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy utility subscription: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy utility subscription' . $e->getMessage(), 500);
        }
    }

}