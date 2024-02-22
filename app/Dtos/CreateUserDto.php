<?php

namespace App\Dtos;

use Spatie\LaravelData\Data;

class CreateUserDto extends Data
{
    /**
     * Create's an instance of CreateUserDto.
     *
     * @param string $request_uuid The UUID of the request.
     * @param string $username The username of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param string|null $referral_code The referral code of WHO referred the current user.
     */
    public function __construct(
        public readonly string $request_uuid,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string|null|Optional $referral_code,
    ) {
    }
}
