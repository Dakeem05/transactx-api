<?php

namespace App\Actions\Auth;

use App\Dtos\Referral\UpdateReferralDto;
use App\Models\User;

class ValidateReferralCodeAction
{
    /**
     * @param UpdateReferralDto $updateReferralDto
     * 
     * @return bool
     */
    public static function handle(UpdateReferralDto $updateReferralDto): bool
    {
        return User::where('referral_code', $updateReferralDto->referral_code)->exists();
    }
}
