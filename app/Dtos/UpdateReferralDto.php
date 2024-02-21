<?php

namespace App\Dtos;

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
