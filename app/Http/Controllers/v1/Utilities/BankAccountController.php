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

    public function listAccounts(): JsonResponse
    {
        try {
            $response = $this->bankAccountService->linkAccount(Auth::user());
            return TransactX::response(true, 'Bank accounts fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('LINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LINK BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to link bank account', 500);
        }
    }
}