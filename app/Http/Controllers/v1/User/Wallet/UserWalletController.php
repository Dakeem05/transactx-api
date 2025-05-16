<?php

namespace App\Http\Controllers\v1\User\Wallet;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        
        $userId = $user->id;

        $wallet = $this->walletService->getUserWalletDeep($userId, Settings::where('name', 'currency')->first()->value);

        return TransactX::response(true, 'Wallet retrieved successfully', 200, $wallet);
    }


    /**
     * This handles creation of wallet
     */
    public function store(Request $request): JsonResponse
    {
        try {

            $user = Auth::user();

            $userId = $user->id;

            // if (!$user->bvnVerified()) {
            //     throw new InvalidArgumentException('You need to verify your BVN before you can create a wallet.');
            // }

            if ($this->walletService->getUserWallet($userId)) {
                throw new InvalidArgumentException('User already has a wallet.');
            }

            $wallet = $this->walletService->createWallet($userId);

            return TransactX::response(true, 'Wallet created successfully.', 201, $wallet);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false,  $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to create wallet.', 500);
        }
    }
}
