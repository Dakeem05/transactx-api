<?php

namespace App\Dtos\User;

use Spatie\LaravelData\Data;

class CreateSubAccountDto extends Data
{
    /**
     * Create's an instance of CreateUserDto.
     *
     * @param string $request_uuid The UUID of the request.
     * @param string $name The name of the user.
     * @param string $username The username of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param string|null $referral_code The referral code of WHO referred the current user.
     */
    public function __construct(
        public readonly string $request_uuid,
        public readonly string $name,
        public readonly string $username,
        public readonly string $password,
    ) {
    }
}
