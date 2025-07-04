<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\BankAccountService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BankAccountController extends Controller
{
    /**
     * Create a new BankAccountController instance.
     */
    public function __construct(
        protected BankAccountService $bankAccountService,
    ) 
    {
        // $this->middleware('user.is.main.account')->except(['show']);
    }

    public function linkAccount(): JsonResponse
    {
        try {
            $response = $this->bankAccountService->linkAccount(Auth::user());

            return TransactX::response(true, 'Bank account linking initiated', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('LINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to link bank account', 500);
        }
    }

    public function relinkAccount(string $ref): JsonResponse
    {
        try {
            $response = $this->bankAccountService->relinkAccount(Auth::user(), $ref);

            return TransactX::response(true, 'Bank account relinking initiated', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('RELINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('RELINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to relink bank account', 500);
        }
    }

    public function listAccounts(): JsonResponse
    {
        try {
            $response = $this->bankAccountService->listAccounts(Auth::user());
            return TransactX::response(true, 'Bank accounts fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('LIST BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LIST BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get bank accounts', 500);
        }
    }

    public function fetchTransactions(string $ref): JsonResponse
    {
        try {
            $response = $this->bankAccountService->fetchTransactions(Auth::user(), $ref);
            return TransactX::response(true, 'Bank transactions fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('FETCH BANK TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('FETCH BANK TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch bank transactions', 500);
        }
    }
}