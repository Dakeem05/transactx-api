<?php

namespace App\Dtos\Referral;

use Spatie\LaravelData\Data;

class UpdateReferralDto extends Data
{
    /**
     * Create's an instance of UpdateReferralDto.
     *
     * @param string $referral_code.
     */
    public function __construct(
        public readonly string $referral_code,
    ) {
    }
}
