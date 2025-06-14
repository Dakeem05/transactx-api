<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Transactions\Payment\SendMoneyToBeneficiaryRequest;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
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
            return TransactX::response(true, 'Banks retrieved successfully.', 200, $this->beneficiaryService->getBeneficiaries(Auth::user()->id, 'payment'));
        } catch (Exception $e) {
            Log::error('get Banks: Error Encountered: ' . $e->getMessage());
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
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }
}