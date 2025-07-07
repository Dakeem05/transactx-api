<?php

namespace App\Http\Controllers\v1\User\Wallet;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Account\CreateWalletRequest;
use App\Http\Requests\User\Account\VerifyUserBVNRequest;
use App\Models\Settings;
use App\Services\User\WalletService;
use App\Services\Utilities\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
    public function initiateCreateWallet(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            if (!$user->bvnVerified()) {
                throw new InvalidArgumentException("You need to verify your BVN before you can create a wallet.");
                // return TransactX::response(false, 'You need to verify your BVN before you can create a wallet.', 400);
            }
            
            if (is_null($user->phone_number)) {
                throw new InvalidArgumentException("You need to update your phone number.");
                // return TransactX::response(false, 'You need to update your phone number.', 400);
            }
            
            if ($this->walletService->getUserWallet($userId)) {
                throw new InvalidArgumentException("User already has a wallet.");
                // return TransactX::response(false, 'User already has a wallet.', 400);
            }

            $verification_data = (object) [
                'user' => $user,
                'bvn' => Crypt::decryptString($user->bvn),
            ];

            $paymentService = resolve(PaymentService::class);
            $verification_response = $paymentService->verifyBVN($verification_data);
                
            return TransactX::response(true, 'Create wallet initiated succesfully.', 200, (object) [
                'verification_id' => $verification_response['data']['verification_id'],
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false,  $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to create wallet. ' . $e->getMessage(), 500);
        }
    }

    public function store(CreateWalletRequest $request): JsonResponse
    {
        try {

            $validatedData = $request->validated();
            $user = Auth::user();

            $userId = $user->id;

            if (!$user->bvnVerified()) {
                throw new InvalidArgumentException("You need to verify your BVN before you can create a wallet.");
            }
            
            if (is_null($user->phone_number)) {
                throw new InvalidArgumentException("You need to update your phone number.");
            }
            
            if ($this->walletService->getUserWallet($userId)) {
                throw new InvalidArgumentException("User already has a wallet.");
            }
            
            $wallet = $this->walletService->createWallet($userId, 
                Settings::where('name', 'currency')->first()->value, 
                Crypt::decryptString($user->bvn), 
                $validatedData['verification_id'], 
                $validatedData['otp']
            );

            return TransactX::response(true, 'Wallet created successfully.', 201, $wallet);
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false,  $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to create wallet.', 500);
        }
    }

    public function destropy(): JsonResponse
    {
        try {
            $user = Auth::user();

            $wallet = $this->walletService->destroy($user->wallet);

            return TransactX::response(true, 'Wallet deleted successfully.', 201, $wallet);
        } catch (InvalidArgumentException $e) {
            Log::error('DESTROY WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false,  $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DESTROY WALLET: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to destroy wallet.', 500);
        }
    }
}
