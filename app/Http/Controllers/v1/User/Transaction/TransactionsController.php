<?php

namespace App\Http\Controllers\v1\User\Transaction;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Transactions\Payment\SendMoneyRequest;
use App\Http\Requests\User\Transactions\QueryUsernameRequest;
use App\Http\Requests\User\Transactions\RequestMoneyFromEmailRequest;
use App\Http\Requests\User\Transactions\RequestMoneyFromUsernameRequest;
use App\Http\Requests\User\Transactions\SendMoneyToEmailRequest;
use App\Http\Requests\User\Transactions\SendMoneyToUsernameRequest;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TransactionsController extends Controller
{
    /**
     * Create a new TransactionsController instance.
     */
    public function __construct(
        protected TransactionService $transactionService,
    ) 
    {
        $this->middleware('user.is.main.account')->except(['show']);
        $this->middleware('user.has.set.transaction.pin');
    }

    public function queryUsers(QueryUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $response = $this->transactionService->queryUsers($validatedData['username'], $user->id);

            return TransactX::response(true, 'Users fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to query users'. $e->getMessage(), 500);
        }
    }
    
    public function sendMoneyToUsername(SendMoneyToUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $this->transactionService->sendMoneyToUsername($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Transfer sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }
    
    public function sendMoneyToEmail(SendMoneyToEmailRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }
            
            $this->transactionService->sendMoneyToEmail($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Transfer sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO EMAIL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO EMAIL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }
    
    public function sendMoney(SendMoneyRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $this->transactionService->sendMoney($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
            return TransactX::response(true, 'Transfer sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }

    public function getRecentRecipients(): JsonResponse
    {
        try {
            $response = $this->transactionService->getRecentRecipients(Auth::user());

            return TransactX::response(true, 'Recent recipients fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('GET RECENT RECIPIENTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET RECENT RECIPIENTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch recent recipients', 500);
        }
    }

    public function getRecentRequestMoneyRecipients(): JsonResponse
    {
        try {
            $response = $this->transactionService->getRecentRequestMoneyRecipients(Auth::user());

            return TransactX::response(true, 'Recent request money recipients fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('GET RECENT REQUEST MONEY RECIPIENTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('GET RECENT REQUEST MONEY RECIPIENTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch recent request money recipients', 500);
        }
    }

    public function getRequestStyles(): JsonResponse
    {
        try {
            $response = $this->transactionService->getRequestStyles();

            return TransactX::response(true, 'Request styles fetched successfully', 200, $response);
        } catch (InvalidArgumentException $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('QUERY USERS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to fetch request styles', 500);
        }
    }

    public function requestMoneyFromUsername(RequestMoneyFromUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $this->transactionService->requestMoneyFromUsername($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
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

            $this->transactionService->requestMoneyFromEmail($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);
            
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

            $history = $this->transactionService->transactionHistory($request, $user);
            
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