<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Services\BuyCableTVServiceRequest;
use App\Http\Requests\User\Services\RetrieveCategoryProductRequest;
use App\Http\Requests\User\Services\VerifyServiceNumberRequest;
use App\Models\Transaction;
use App\Services\Utilities\CableTVService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CableTVServiceController extends Controller
{
    public function __construct(
        protected CableTVService $cabletvService,
    ) {
        $this->middleware('user.is.main.account')->except(['getNetworks']);
    }

    public function getCompanies()
    {
        try {
            $companies = $this->cabletvService->getCompanies();
            return TransactX::response(true, 'Companies fetched successfully', 200, $companies);
        } catch (InvalidArgumentException $e) {
            Log::error('Get companies: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get companies: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get companies', 500);
        }
    }

    public function getPackages(RetrieveCategoryProductRequest $request)
    {
        try {
            $packages = $this->cabletvService->getPackages($request->validated());
            return TransactX::response(true, 'Cable TV packages fetched successfully', 200, $packages);
        } catch (InvalidArgumentException $e) {
            Log::error('Get packages: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get packages: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get cable tv packages', 500);
        }
    }

    public function verifyNumber(VerifyServiceNumberRequest $request)
    {
        try {
            $data = $this->cabletvService->verifyNumber($request->validated());
            return TransactX::response(true, 'Cable TV number verified successfully', 200, $data);
        } catch (InvalidArgumentException $e) {
            Log::error('Verify cable tv number: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Verify cable tv number: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to verify cable tv number', 500);
        }
    }

    public function buySubscription(BuyCableTVServiceRequest $request)
    {
        try {
            $this->cabletvService->buySubscription($request->validated(), Auth::user());
            return TransactX::response(true, 'Cable TV subscription bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('Buy cable tv subscription: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy cable tv subscription: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy cable tv subscription', 500);
        }
    }

}