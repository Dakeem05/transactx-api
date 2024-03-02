<?php

namespace App\Actions\Auth;

use App\Dtos\User\CreateUserDto;
use App\Dtos\User\CompleteUserRegistrationDto;
use App\Enums\UserStatusEnum;
use App\Events\User\UserCreatedEvent;
use App\Jobs\User\CompleteUserRegistration;
use App\Models\Role;
use App\Models\User;
use Facades\App\Services\UserService;
use Illuminate\Http\Request;
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
    public static function handle(CreateUserDto $createUserDto, Request $request)
    {
        return DB::transaction(function () use ($createUserDto, $request) {

            // Create user
            $user = User::create([
                'username' => $createUserDto->username,
                'email' => $createUserDto->email,
                'password' => $createUserDto->password,
                'role_id' => Role::user_role_id(),
                'status' => UserStatusEnum::NEW,
                'referred_by_user_id' => $createUserDto->referral_code ? UserService::get_user_by_ref_code($createUserDto->referral_code, 'id') : null,
            ]);

            $user->generate_referral_code();
            $user->save_country_from_ip($request);

            event(new UserCreatedEvent(CompleteUserRegistrationDto::from(['user' => $user])));

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
