<?php

namespace App\Actions\Auth;

use App\Dtos\User\CreateUserDto;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\Role;
use App\Models\User;
use Exception;
use Facades\App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterUserAction
{
    /**
     * Handle the registration process for a new user.
     *
     * @param CreateUserDto $createUserDto The data transfer object containing user information.
     * @param Request $request The request
     * @return User|null Returns the registered user object or null if an error occurs.
     */
    public static function handle(CreateUserDto $createUserDto, Request $request)
    {
        return DB::transaction(function () use ($createUserDto, $request) {
            // Create user
            $user = User::create([
                'name' => isset($createUserDto->organization_name) ? $createUserDto->organization_name : $createUserDto->first_name . ' ' . $createUserDto->last_name,
                'username' => $createUserDto->username,
                'email' => $createUserDto->email,
                'password' => $createUserDto->password,
                'user_type' => isset($createUserDto->organization_name) ? UserTypeEnum::organization : UserTypeEnum::individual,
                'role_id' => Role::getUserRoleId(),
                'status' => UserStatusEnum::NEW,
                'referred_by_user_id' => $createUserDto->referral_code ? UserService::getUserByRefCode($createUserDto->referral_code, 'id') : null,
            ]);

            //$user->generate_referral_code(); TO BE CALLED AFTER UPDATING PROFILE

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
