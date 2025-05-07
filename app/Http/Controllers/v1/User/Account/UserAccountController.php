<?php

namespace App\Http\Controllers\v1\User\Account;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Account\UpdateUserAccountRequest;
use App\Http\Requests\User\Account\VerifyUserBVNRequest;
use App\Models\User;
use App\Services\UserService;
use App\Services\Utilities\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserAccountController extends Controller
{

    /**
     * Create a new UserAccountController instance.
     */
    public function __construct(
        protected UserService $userService,
    ) {
    }


    /**
     * Return the user account
     */
    public function show(): JsonResponse
    {
        try {
            $user = auth()->user();

            $user = $this->userService->getUserById($user->id);

            return TransactX::response(true, 'User account retrieved successfully', 200, (object) ["user" => $user]);
        } catch (Exception $e) {
            Log::error('GET USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
    
    
    /**
     * Update user account (profile)
     */
    public function update(UpdateUserAccountRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            $user = auth()->user();
            
            // Names can only change if user is not verified
            if (isset($validatedData['name']) && $user->name !== $validatedData['name'] && $user->kycVerified()) {
                throw new InvalidArgumentException("Name cannot be changed after KYC verification");
            }
            
            $user = $this->userService->updateUserAccount($user, $validatedData);
            
            return TransactX::response(true, 'User account updated successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update user account', 500);
        }
    }
    
    
    /**
     * Verify a User BVN
     */
    public function verifyBVN(VerifyUserBVNRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();
            $bvn = $validatedData['bvn'];
            $nin = $validatedData['nin'];
            $bank_code = $validatedData['bank_code'] ?? null;
            $account_number = $validatedData['account_number'] ?? null;

            if ($user->bvnVerified()) {
                throw new InvalidArgumentException("BVN has already been verified");
            }

            $verification_data = (object) [
                'user' => $user,
                'bvn' => $bvn,
                'nin' => $nin,
                'account_number' => $account_number,
                'bank_code' => $bank_code,
            ];

            $paymentService = resolve(PaymentService::class);
            $verification_response = $paymentService->verifyBVN($verification_data);
                
            return TransactX::response(true, $verification_response, 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to validate user bvn', 500);

        }
    }
}
