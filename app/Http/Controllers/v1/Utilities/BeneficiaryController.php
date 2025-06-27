<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Services\BuyCableTVToBeneficiaryRequest;
use App\Http\Requests\User\Services\BuyUtilityToBeneficiaryRequest;
use App\Http\Requests\User\Services\BuyAirtimeToBeneficiaryRequest;
use App\Http\Requests\User\Transactions\Payment\SendMoneyToBeneficiaryRequest;
use App\Http\Requests\User\Services\BuyDataToBeneficiaryRequest;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Services\Utilities\AirtimeService;
use App\Services\Utilities\CableTVService;
use App\Services\Utilities\DataService;
use App\Services\Utilities\UtilityService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BeneficiaryController extends Controller
{
    public $beneficiaryService;

    public function __construct(
    ) {
        $this->beneficiaryService = resolve(BeneficiaryService::class);
    }

    public function getBeneficiaries()
    {
        try {
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'payment'));
        } catch (Exception $e) {
            Log::error('get Beneficiaries: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function getAirtimeBeneficiaries()
    {
        try {
            return TransactX::response(true, 'Airtime beneficiaries retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'airtime'));
        } catch (Exception $e) {
            Log::error('get Airtime Beneficiaries: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function getDataBeneficiaries()
    {
        try {
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'data'));
        } catch (Exception $e) {
            Log::error('get Beneficiaries: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function getCableTVBeneficiaries()
    {
        try {
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'cabletv'));
        } catch (Exception $e) {
            Log::error('get Beneficiaries: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
    
    public function getUtilityBeneficiaries()
    {
        try {
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'utility'));
        } catch (Exception $e) {
            Log::error('get Beneficiaries: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function searchBeneficiaries($query)
    {
        try {
            $beneficiaries = $this->beneficiaryService->searchBeneficiaries(Auth::user()->id, $query, 'payment');
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('search Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function searchAirtimeBeneficiaries($query)
    {
        try {
            $beneficiaries = $this->beneficiaryService->searchAirtimeOrDataBeneficiaries(Auth::user()->id, $query, 'airtime');
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('search Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function searchCableTVBeneficiaries($query)
    {
        try {
            $beneficiaries = $this->beneficiaryService->searchCableTVOrUtilityBeneficiaries(Auth::user()->id, $query, 'cabletv');
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('search Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function searchUtilityBeneficiaries($query)
    {
        try {
            $beneficiaries = $this->beneficiaryService->searchCableTVOrUtilityBeneficiaries(Auth::user()->id, $query, 'utility');
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('search Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function searchDataBeneficiaries($query)
    {
        try {
            $beneficiaries = $this->beneficiaryService->searchAirtimeOrDataBeneficiaries(Auth::user()->id, $query, 'data');
            return TransactX::response(true, 'Beneficiaries retrieved successfully.', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('search Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function deleteBeneficiary($id)
    {
        try {
            $beneficiary = $this->beneficiaryService->getBeneficiary(Auth::user()->id, $id);
            if (!$beneficiary) {
                return TransactX::response(false, 'Beneficiary not found.', 404);
            }

            $beneficiary->delete();
            return TransactX::response(true, 'Beneficiary deleted successfully.', 200);
        } catch (Exception $e) {
            Log::error('delete Beneficiary: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    public function sendMoney(SendMoneyToBeneficiaryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $transactionService = resolve(TransactionService::class);
            $transactionService->sendMoneyToBeneficiary($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Transfer sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }

    public function buyAirtime(BuyAirtimeToBeneficiaryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $airtimeService = resolve(AirtimeService::class);
            $airtimeService->buyAirtimeToBeneficiary($validatedData, $user);
            
            return TransactX::response(true, 'Airtime bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('BUY AIRTIME TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('BUY AIRTIME TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy airtime: ' . $e->getMessage(), 500);
        }
    }

    public function buyData(BuyDataToBeneficiaryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $dataService = resolve(DataService::class);
            $dataService->buyDataToBeneficiary($validatedData, $user);
            
            return TransactX::response(true, 'Data bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('BUY DATA TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('BUY DATA TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy data: ' . $e->getMessage(), 500);
        }
    }

    public function buyCableTVSub(BuyCableTVToBeneficiaryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $cableTVService = resolve(CableTVService::class);
            $cableTVService->buyToBeneficiary($validatedData, $user);
            
            return TransactX::response(true, 'Cable TV subscription bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('Buy cable tv subscription TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy cable tv subscription TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy cable TV subscription: ' . $e->getMessage(), 500);
        }
    }

    public function buyUtilitySub(BuyUtilityToBeneficiaryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $utilityService = resolve(UtilityService::class);
            $utilityService->buyToBeneficiary($validatedData, $user);
            
            return TransactX::response(true, 'Utility subscription bought successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('Buy utility subscription TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Buy utility subscription TO BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to buy utility subscription: ' . $e->getMessage(), 500);
        }
    }
}