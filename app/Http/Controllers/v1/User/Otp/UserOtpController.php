<?php

namespace App\Http\Controllers\v1\User\Otp;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Otp\SendVerificationCodeRequest;
use App\Http\Requests\User\Otp\VerifyVerificationCodeRequest;
use App\Services\OTPService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserOtpController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        public OTPService $otpService
    ) {
    }


    /**
     * This handles generation and sending of otp
     */
    public function send(SendVerificationCodeRequest $request): JsonResponse
    {
        try {

            $payload = $request->validated();

            $expiryMinutes = 10;

            $expiry = now()->addMinutes($expiryMinutes);

            $this->otpService->generateAndSendOTP(
                $payload['phone'] ?? null,
                $payload['email'] ?? null,
                $expiryMinutes
            );

            return TransactX::response([
                'message' => 'Verification code sent.',
                'expires_at' => $expiry
            ], 200);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('SEND VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('SEND VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Failed to send verification code'], 500);
        }
    }



    /**
     * This handles validation of otp
     */
    public function verify(VerifyVerificationCodeRequest $request): JsonResponse
    {
        try {

            $payload = $request->validated();

            $otp_identifier = $this->otpService->getVerificationCodeIdentifier(
                $payload['phone'] ?? null,
                $payload['email'] ?? null,
                $payload['verification_code']
            );

            $this->otpService->verifyOTP(
                $payload['phone'] ?? null,
                $payload['email'] ?? null,
                $payload['verification_code'],
                $otp_identifier
            );

            return TransactX::response([
                'message' => 'Code valid',
                'otp_identifier' => $otp_identifier
            ], 200);
            // 
        } catch (ModelNotFoundException $e) {
            Log::error('VERIFY VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('VERIFY VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(['message' => 'Failed to verify verification code'], 500);
        }
    }
}
