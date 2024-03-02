<?php

namespace App\Dtos\User;

use App\Models\User;
use Spatie\LaravelData\Data;


class CompleteUserRegistrationDto extends Data
{
    /**
     * Create's an instance of CompleteUserRegistrationDto.
     *
     * @param User $user
     */
    public function __construct(
        public readonly User $user,
    ) {
    }
}
