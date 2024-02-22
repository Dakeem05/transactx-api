<?php

namespace App\Actions\Auth;

use App\Dtos\CreateUserDto;
use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterUserAction
{
    /**
     * Handle the registration process for a new user.
     *
     * @param CreateUserDto $data The data transfer object containing user information.
     * @return User|null Returns the registered user object or null if an error occurs.
     * @throws \Throwable Throws an exception if a transactional operation fails.
     */
    public static function handle(CreateUserDto $createUserDto)
    {
        return DB::transaction(function () use ($createUserDto) {

            // Create user
            $user = User::create([
                'username' => $createUserDto->username,
                'email' => $createUserDto->email,
                'password' => $createUserDto->password,
                'referral_code' => UserService::generate_referral_code(),
                'role_id' => Role::user_role_id(),
                'status' => UserStatusEnum::NEW,
                'referred_by_user_id' => $createUserDto->referral_code ? UserService::get_user_by_ref_code($createUserDto->referral_code, 'id') : null,
            ]);

            /* --------------------------- Notify the Referrer -------------------------- */

            //Dispatcha job to handle avatar to cloudinay and the call notification
            // $user->notify(new NewUserRegistered($user));

            Log::channel('daily')->info('REGISTER: END', [
                "uid" => $createUserDto->request_uuid,
                "response" => [
                    'data' => $user,
                ],
            ]);

            return $user;
        });
    }
}
