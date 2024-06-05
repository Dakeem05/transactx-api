<?php

namespace App\Http\Controllers\v1\User\Account;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Account\UpdateUserAccountRequest;
use App\Http\Requests\User\Account\ValidateUserBVNRequest;
use App\Models\User;
use App\Services\External\PaystackService;
use App\Services\UserService;
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

            return TransactX::response([
                'message' => 'User account retrieved successfully',
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('GET USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update user account (profile)
     */
    public function update(UpdateUserAccountRequest $request, string $userId): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = auth()->user();

            // Names can only change if user is not verified
            if (isset($validatedData['name']) && $user->name !== $validatedData['name'] && $user->kycVerified()) {
                throw new InvalidArgumentException("Name cannot be changed");
            }

            $user = $this->userService->updateUserAccount($user, $validatedData);

            return TransactX::response([
                'message' => 'User account updated successfully',
                'user' => $user
            ], 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Failed to update user account'], 500);
        }
    }


    /**
     * Validate User BVN
     */
    public function validateBVN(ValidateUserBVNRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();
            $bvn = $validatedData['bvn'];
            $bank_code = $validatedData['bank_code'];
            $account_number = $validatedData['account_number'];
            $customer_code = $user->customer_code;

            // Suspend user if attempting to use an already used BVN
            // if (User::where('bvn', $bvn)->exists()) {
            //     $user->suspend();
            //     throw new InvalidArgumentException('Your account has been suspended.');
            // }

            // Ensure user already has a customer code
            // if (!$user->hasCustomerCode()) {
            //     throw new InvalidArgumentException('Cannot proceed to validate BVN. Ensure your mobile number is updated.');
            // }

            $paystackService = resolve(PaystackService::class);

            $test = $paystackService->validateCustomer($customer_code, $user->first_name, $user->last_name, $account_number, $bvn, $bank_code);

            $user = $this->userService->updateUserAccount($user, [
                'bvn' => $bvn,
                'bvn_status' => 'PENDING'
            ]);

            return TransactX::response([
                'message' => 'BVN Verification submitted successfully.',
                'test' => $test
            ], 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VALIDATE USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('VALIDATE USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Failed to validate user bvn'], 500);
        }
    }
}
