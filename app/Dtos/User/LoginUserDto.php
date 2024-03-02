<?php

namespace App\Dtos\User;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class LoginUserDto extends Data
{
    /**
     * Create's an instance of LoginUserDto.
     *
     * @param string $request_uuid The UUID of the request.
     * @param string $username The username of the user.
     * @param string $password The password of the user.
     * @param string $fcm_device_token The fcm token of the user's device.
     */
    public function __construct(
        public readonly string $request_uuid,
        public readonly string $username,
        public readonly string $password,
        public readonly string|Optional|null $fcm_device_token,
    ) {
    }
}
