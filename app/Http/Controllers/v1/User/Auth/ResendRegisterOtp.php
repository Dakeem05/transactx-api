<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Requests\User\ResendRegisterOtpRequest;
use App\Models\VerificationCode;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ResendRegisterOtp extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResendRegisterOtpRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $verification_code = VerificationCode::where('email', $validatedData['email'])
                ->where('purpose', 'verification')
                ->where('is_verified', false)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been requested");
            }

            $verification_code->delete();

            $otpService = resolve(UserOtpController::class);
            $response = $otpService->sendForVerification(
                $validatedData['email'],
                null,
            );

            if (!$response->status) {
                throw new Exception('Failed to send verification code');
            }

            return TransactX::response(true, 'Otp has been resent', 200, (object)['expires_at' => $response->expires_at]);
        } catch (Exception $e) {
            Log::error('RESEND REGISTER OTP: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
