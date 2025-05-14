<?php

namespace App\Http\Controllers\v1\User\Transaction;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Requests\User\Account\CreateTransactionPinRequest;
use App\Http\Requests\User\Otp\VerifyAppliedVerificationCodeRequest;
use App\Models\VerificationCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TransactionPinController extends Controller
{
    /**
     * Create a new TransactionPinController instance.
     */
    public function __construct(
        protected UserService $userService,
    ) {
    }
    
    public function setTransactionPin(CreateTransactionPinRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();
            $pin = $validatedData['pin'];

            if ($user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has already been set");
            }

            $user = $this->userService->updateUserAccount($user, ['transaction_pin' => $pin]);

            return TransactX::response(true, 'Transaction pin set successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('SET TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SET TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to set transaction pin'. $e->getMessage(), 500);
        }
    }

    public function changeTransactionPin(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $user->email,
                null,
                'change_transaction_pin'
            );

            if (!$response->status) {
                throw new Exception('Failed to send verification code');
            }

            return TransactX::response(true, 'Otp has been sent to your email', 200, (object)['expires_at' => $response->expires_at]);
        } catch (InvalidArgumentException $e) {
            Log::error('CHANGE TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CHANGE TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to change transaction pin', 500);
        }
    }

    public function resendTransactionPinOtp(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $verification_code = VerificationCode::where('email', $user->email)
                ->where('purpose', 'change_transaction_pin')
                ->where('is_verified', false)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been requested");
            }

            $verification_code->delete();

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $user->email,
                null,
                'change_transaction_pin'
            );

            if (!$response->status) {
                throw new Exception('Failed to send verification code');
            }

            return TransactX::response(true, 'Otp has been resent', 200, (object)['expires_at' => $response->expires_at]);
        } catch (InvalidArgumentException $e) {
            Log::error('RESEND TRANSACTION PIN OTP: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('RESEND TRANSACTION PIN OTP: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to resend transaction pin otp', 500);
        }
    }

    public function verifyTransactionPinOtp(VerifyAppliedVerificationCodeRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->verifyAppliedCode($user->email, $validatedData['verification_code'], null, 'change_transaction_pin');


            if (!$response->status) {
                throw new Exception('Failed to verify otp');
            }

            return TransactX::response(true, 'Otp has been verified', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY TRANSACTION PIN OTP: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY TRANSACTION PIN OTP: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to verify otp', 500);
        }
    }

    public function updateTransactionPin(CreateTransactionPinRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();
            $pin = $validatedData['pin'];

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            $verification_code = VerificationCode::where('email', $user->email)
                ->where('purpose', 'change_transaction_pin')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $user = $this->userService->updateUserAccount($user, ['transaction_pin' => $pin]);

            return TransactX::response(true, 'Transaction pin updated successfully', 200, (object) ["user" => $user]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update transaction pin', 500);
        }
    }

    public function verifyTransactionPin (CreateTransactionPinRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = auth()->user();
            $pin = $validatedData['pin'];

            if (!$user->transaction_pin) {
                throw new InvalidArgumentException("Transaction pin has not been set");
            }

            if (!Hash::check($pin, $user->transaction_pin)) {
                throw new InvalidArgumentException("Transaction pin is incorrect");
            }

            return TransactX::response(true, 'Transaction pin verified successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('VERIFY TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY TRANSACTION PIN: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to verify transaction pin', 500);
        }
    }
}