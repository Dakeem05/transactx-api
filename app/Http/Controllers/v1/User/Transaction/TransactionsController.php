<?php

namespace App\Http\Controllers\v1\User\Transaction;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Transactions\Payment\SendMoneyRequest;
use App\Http\Requests\User\Transactions\QueryUsernameRequest;
use App\Http\Requests\User\Transactions\SendMoneyToEmailRequest;
use App\Http\Requests\User\Transactions\SendMoneyToUsernameRequest;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
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
    }

    public function queryUsers(QueryUsernameRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

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
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

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
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $this->transactionService->sendMoneyToEmail($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);

            return TransactX::response(true, 'Transfer sent successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND MONEY TO USERNAME: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send money: ' . $e->getMessage(), 500);
        }
    }

    public function sendMoney(SendMoneyRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $this->transactionService->sendMoney($validatedData, $user, explode(',', $request->header('X-Forwarded-For'))[0]);

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