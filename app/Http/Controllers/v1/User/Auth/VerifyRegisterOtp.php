<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Events\User\UserCreatedEvent;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Requests\User\VerifyRegisterOtpRequest;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class VerifyRegisterOtp extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(VerifyRegisterOtpRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->verifyAppliedCode($validatedData['email'], $validatedData['verification_code'], null, 'verification');

            if (!$response->status) {
                throw new Exception('Failed to verify otp');
            }

            $verification_code = VerificationCode::where('email', $validatedData['email'])
                ->where('purpose', 'verification')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $userService = resolve(UserService::class);
            $userService->updateUserAccount(User::where('email', $validatedData['email'])->first(), [
                'email_verified_at' => Carbon::now(),
            ]);

            event(new UserCreatedEvent($user));

            return TransactX::response(true, 'Otp has been verified', 200);
        } catch (Exception $e) {
            Log::error('VERIFY REGISTER OTP: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
