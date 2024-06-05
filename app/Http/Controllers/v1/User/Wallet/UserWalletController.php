<?php

namespace App\Http\Controllers\v1\User\Wallet;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserWalletController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        public WalletService $walletService
    ) {
    }


    /**
     * This handles creation of wallet
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $userId = $user->id;

        $wallet = $this->walletService->getUserWallet($userId);

        return TransactX::response([
            'message' => 'Wallet retrieved successfully.',
            'wallet' => $wallet
        ], 200);
    }


    /**
     * This handles creation of wallet
     */
    public function store(Request $request): JsonResponse
    {
        try {

            $user = auth()->user();

            $userId = $user->id;

            if ($this->walletService->getUserWallet($userId)) {
                throw new InvalidArgumentException('User already has a wallet.');
            }

            $wallet = $this->walletService->createWallet($userId);

            return TransactX::response([
                'message' => 'Wallet created successfully.',
                'wallet' => $wallet
            ], 201);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Failed to create wallet.'], 500);
        }
    }
}
