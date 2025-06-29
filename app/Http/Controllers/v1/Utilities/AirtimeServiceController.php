<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Services\BuyAirtimeServiceRequest;
use App\Models\Transaction;
use App\Services\Utilities\AirtimeService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AirtimeServiceController extends Controller
{
    public function __construct(
        protected AirtimeService $airtimeService,
    ) {
        $this->middleware('user.is.main.account')->except(['getNetworks']);
    }

    public function getNetworks()
    {
        try {
            $networks = $this->airtimeService->getNetworks();
            return TransactX::response(true, 'Networks fetched successfully', 200, $networks);
        } catch (InvalidArgumentException $e) {
            Log::error('Get network: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get network: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get networks', 500);
        }
    }

    public function buyAirtime(BuyAirtimeServiceRequest $request)
    {
        try {
            $this->airtimeService->buyAirtime($request->validated(), Auth::user());
            return TransactX::response(true, 'Airtime bought successfully', 200, );
        } catch (InvalidArgumentException $e) {
            Log::error('Buy airtime: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy airtime: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy airtime', 500);
        }
    }
}