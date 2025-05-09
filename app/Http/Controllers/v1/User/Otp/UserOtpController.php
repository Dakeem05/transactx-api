<?php

namespace App\Http\Controllers\v1\User\Otp;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Otp\SendVerificationCodeRequest;
use App\Http\Requests\User\Otp\VerifyAppliedVerificationCodeRequest;
use App\Http\Requests\User\Otp\VerifyVerificationCodeRequest;
use App\Services\OTPService;
use Error;
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

            return TransactX::response(true, 'Verification code sent.', 200, (object)['expires_at' => $expiry]);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('SEND VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('SEND VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to send verification code', 500);
        }
    }

    /**
     * This handles generation and sending of otp for applied verifications
     */
    public function sendForVerification($email, $phone = null, $purpose = null)
    {
        try {
            $expiryMinutes = 10;

            $expiry = now()->addMinutes($expiryMinutes);

            $this->otpService->generateAndSendOTP(
                $phone ?? null,
                $email ?? null,
                $expiryMinutes,
                $purpose = $purpose ?? 'verification',
            );

            return (object)[
                'status' => true,
                'expires_at' => $expiry
            ];
            // 
        } catch (Exception $e) {
            throw new Exception('Failed to send verification code');
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

            return TransactX::response(true, 'Otp verified', 200);
            // 
        } catch (ModelNotFoundException $e) {
            Log::error('VERIFY VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('VERIFY VERIFICATION CODE: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to verify verification code', 500);
        }
    }

    /**
     * This handles validation of otp
     */
    public function verifyAppliedCode($email, $code, $phone = null, $purpose = null)
    {
        try {
            $otp_identifier = $this->otpService->getVerificationCodeIdentifier(
                $phone ?? null,
                $email ?? null,
                $code
            );

            $verification_code = $this->otpService->verifyOTP(
                $phone ?? null,
                $email ?? null,
                $code,
                $otp_identifier
            );

            if ($verification_code->purpose !== $purpose) {
                throw new InvalidArgumentException('Verification code does not match the purpose');
            }

            return (object)[
                'status' => true,
                'verification_code' => $verification_code
            ];
            // 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
