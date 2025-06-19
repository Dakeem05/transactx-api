<?php

namespace App\Http\Controllers\v1\User\Account;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Requests\User\Account\UpdateUserAccountRequest;
use App\Http\Requests\User\Account\UpdateUserAvatarRequest;
use App\Http\Requests\User\Account\ValidateUserBVNRequest;
use App\Http\Requests\User\Account\VerifyUserBVNRequest;
use App\Http\Requests\User\Otp\VerifyAppliedVerificationCodeRequest;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\User\WalletService;
use App\Services\UserService;
use App\Services\Utilities\PaymentService;
use Brick\Money\Money;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
            $user = Auth::user();

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
            
            $user = Auth::user();
            
            // Names can only change if user is not verified
            if (isset($validatedData['name']) && $user->name !== $validatedData['name'] && $user->kycVerified()) {
                throw new InvalidArgumentException("Name cannot be changed after KYC verification");
            }

            if (isset($validatedData['profile'])) {
                $uploadedFile = $validatedData['avatar'];
                $result = cloudinary()->upload($uploadedFile->getRealPath())->getSecurePath();
                $validatedData['avatar'] = $result;
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
     * Update user account (profile)
     */
    public function updateAvatar(UpdateUserAvatarRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            $user = Auth::user();
            
            $uploadedFile = $validatedData['avatar'];
            
            $result = cloudinary()->upload($uploadedFile->getRealPath())->getSecurePath();

            $data = [
                'avatar' =>  $result
            ];

            $user = $this->userService->updateUserAccount($user, $data);
            
            return TransactX::response(true, 'User avatar updated successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER AVATAR: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER AVATAR: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update user avatar', 500);
        }
    }
    
    
    public function initiateBvnVerification(VerifyUserBVNRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            $bvn = $validatedData['bvn'];
            $nin = $validatedData['nin'] ?? null;
            $bank_code = $validatedData['bank_code'] ?? null;
            $account_number = $validatedData['account_number'] ?? null;

            if ($user->bvnVerified()) {
                throw new InvalidArgumentException("BVN has already been verified");
            }

            // SUPPOSED TO CHECK IF VN HAS BEEN VERIFIED BEFORE 
            if (User::withBvn($validatedData['bvn'])->exists()) {
                throw new InvalidArgumentException("BVN already exists");
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
                
            return TransactX::response(true, $verification_response['message'], 200, (object) [
                'verification_id' => $verification_response['data']['verification_id'],
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            // return TransactX::response(false, 'Failed to verify user bvn', 500);

            return TransactX::response(false, 'Failed to validate user bvn'.$e->getMessage(), 500);

        }
    }

    public function validateBvnVerification(ValidateUserBVNRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            if ($user->bvnVerified()) {
                throw new InvalidArgumentException("BVN has already been verified");
            }

            // SUPPOSED TO CHECK IF VN HAS BEEN VERIFIED BEFORE 
            if (User::withBvn($validatedData['bvn'])->exists()) {
                throw new InvalidArgumentException("BVN already exists");
            }

            $verification_data = (object) [
                'user' => $user,
                'otp' =>  $validatedData['otp'],
                'bvn' =>  $validatedData['bvn'],
                'verification_id' =>  $validatedData['verification_id'],
            ];

            $paymentService = resolve(PaymentService::class);
            $verification_response = $paymentService->validateBVN($verification_data);
                
            return TransactX::response(true, $verification_response, 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to validate user bvn' , 500);
        }
    }

    public function destroy (): JsonResponse
    {
        try {
            $user = Auth::user();

            $wallet = $user->wallet;

            $wallet = $user->wallet;

            if (!is_null($wallet)) {
                $amount = Money::of(0, $wallet->currency);
                
                if ($wallet->amount->isGreaterThan($amount)) {
                    throw new InvalidArgumentException("You have to empty your balance before deleting your account.");
                }
            }

            $verification_code = VerificationCode::where('email', $user->email)->where('purpose', 'delete_user_account')->first();
            if (!is_null($verification_code)) {
                $verification_code->delete();
            }

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $user->email,
                null,
                'delete_user_account'
            );

            if (!$response->status) {
                throw new Exception('Failed to send verification code');
            }
            return TransactX::response(true, 'Otp has been sent to your email', 200, (object)['expires_at' => $response->expires_at]);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to delete user account', 500);
        }
    }

    public function verifyOtpAndDeleteAccount (VerifyAppliedVerificationCodeRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();

            $wallet = $user->wallet;

            if (!is_null($wallet)) {
                $amount = Money::of(0, $wallet->currency);
                
                if ($wallet->amount->isGreaterThan($amount)) {
                    throw new InvalidArgumentException("You have to empty your balance before deleting your account.");
                }
            }

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->verifyAppliedCode($user->email, $validatedData['verification_code'], null, 'delete_user_account');

            if (!$response->status) {
                throw new Exception('Failed to verify otp');
            }

            $verification_code = VerificationCode::find($response->verification_code->id);

            if (!$verification_code) {
                throw new Exception('Could not find verification code');
            }

            if (!is_null($wallet)) {
                $walletService = resolve(WalletService::class);
                $walletService->destroy($wallet);
            }

            $verification_code->delete();

            if (!empty($user->subAccounts)) {
                foreach ($user->subAccounts as $sub_account) {
                    $sub_account->delete();
                }
            }

            $user->delete();

            return TransactX::response(true, 'Account deleted succesfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to delete user account', 500);
        }
    }
}
