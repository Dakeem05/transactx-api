<?php

namespace App\Http\Controllers\v1\Utilities;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Transactions\Payment\SendMoneyRequest;
use App\Http\Requests\User\Transactions\QueryUsernameRequest;
use App\Http\Requests\User\Transactions\RequestMoneyFromEmailRequest;
use App\Http\Requests\User\Transactions\RequestMoneyFromUsernameRequest;
use App\Services\BankAccountService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $this->middleware('user.has.set.transaction.pin');
    }

    public function queryUsers(QueryUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $response = $this->bankAccountService->queryUsers($validatedData['username'], $user->id);

            return TransactX::response(true, 'Users fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to query users'. $e->getMessage(), 500);
        }
    }
    
    public function requestMoneyFromUsername(RequestMoneyFromUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $this->bankAccountService->requestMoneyFromUsername($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Request sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to request money: ' . $e->getMessage(), 500);
        }
    }

    public function requestMoneyFromEmail(RequestMoneyFromEmailRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $this->bankAccountService->requestMoneyFromEmail($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Request sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to request money: ' . $e->getMessage(), 500);
        }
    }

    public function transactionHistory(Request $request)
    {
        try {
            $user = Auth::user();

            $history = $this->bankAccountService->transactionHistory($request, $user);
            
            return TransactX::response(true, 'Transaction history retrieved successfully', 200, $history);
        } catch (InvalidArgumentException $e) {
            Log::error('Get Transaction History: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Get Transaction History: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to get transaction history: ' . $e->getMessage(), 500);
        }
    }
}