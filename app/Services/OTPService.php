<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Notifications\User\Otp\VerificationCodeNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OTPService
{


    /**
     * This function runs analysis to detect spam on the otp module
     * 
     * @param string|null $phone
     * @param string|null $email
     */
    private function runSecurityChecks($phone, $email)
    {

        /**
         * pick the user IP, 
         * if it has requested phone OTP more than 5 times in the last 48 hours
         * throw InvalidArgumentException
         */

        $user_ip = request()->ip();

        $count = VerificationCode::where('user_ip', $user_ip)
            ->where('created_at', '>', now()->subDays(2))
            ->count();

        if ($count > 5) {
            logger()->info("$count OTP requests in the last 48 hours from IP $user_ip.. possible spam");
            throw new InvalidArgumentException("You have reached the maximum number of OTP requests for this device.");
        }
    }



    /**
     * This function generates and send otp
     * 
     * @param string|null $phone
     * @param string|null $email
     * @param int $expiryMinutes
     * @return string
     */
    public function generateAndSendOTP($phone = null, $email = null, $expiryMinutes = 10)
    {
        $code = rand(100000, 999999); // Generate a random 6-digit OTP

        $identifier = (string) Str::uuid();

        $user_ip = request()->ip();

        // $this->runSecurityChecks($phone, $email);

        VerificationCode::create([
            'identifier' => $identifier,
            'phone' => $phone,
            'email' => $email,
            'code' => $code,
            'user_ip' => $user_ip,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        if (!is_null($email)) {
            Notification::route('mail', $email)->notify(new VerificationCodeNotification($code, $expiryMinutes));
        } else {
            throw new InvalidArgumentException("An email address must be provided.");
        }

        return $identifier;
    }


    /**
     * This function gets the identifier of a verification code
     * 
     * @param string|null $phone
     * @param string|null $email
     * @param string|null $code
     * @return string
     */
    public function getVerificationCodeIdentifier($phone = null, $email = null, $code)
    {

        if (is_null($code)) {
            throw new InvalidArgumentException("Verification code must be provided.");
        }

        $query = VerificationCode::where('code', $code)
            ->where('expires_at', '>', now());

        if (!is_null($phone) || !is_null($email)) {
            $query->where(function ($q) use ($phone, $email) {
                if (!is_null($phone)) {
                    $q->orWhere('phone', $phone);
                }
                if (!is_null($email)) {
                    $q->orWhere('email', $email);
                }
            });
        } else {
            throw new InvalidArgumentException("Either phone or email must be provided.");
        }

        $otp = $query->latest()->first();

        if (!$otp) {
            throw new ModelNotFoundException("Verification code not found or has expired.");
        }

        return $otp->identifier;
    }



    /**
     * This function verifies the otp code supplied
     * 
     * @param string|null $phone
     * @param string|null $email
     * @param string $code
     * @param string $identifier
     * @return void
     */
    public function verifyOTP($phone = null, $email = null, $code, $identifier)
    {
        $id = $this->getVerificationCodeIdentifier($phone, $email, $code);

        if ($id !== $identifier) {
            throw new InvalidArgumentException("Invalid verification code identifier.");
        }

        VerificationCode::where('identifier', $identifier)
            ->update(['expires_at' => now()]);
    }
}
