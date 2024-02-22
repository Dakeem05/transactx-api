<?php

namespace App\Actions\Auth;

use App\Dtos\CreateUserDto;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($data) {

            /* ------------------------------------ Add system generated user's referral code ----------------------------------- */
            $user = User::create(array_merge($createUserDto->toArray(), [
                'referral_code' => UserService::generateReferralCode(),
                'role_id' => Role::user_role_id()
            ]));

            /* --------------------------- Update Referred By --------------------------- */
            if ($createUserDto->referral_code != null) {
                $payload = UpdateReferralDto::from([
                    'user' => $user,
                    'referral_code' => $createUserDto->referral_code,
                ]);

                $referred_by_user = UpdateReferralAction::handle($payload);

                if ($referred_by_user != null) {
                    /* --------------------------- Notify the Referrer -------------------------- */
                    $referred_by_user->notify(new NewReferrerAlert(
                        $referred_by_user->fullname,
                        $user->fullname,
                        $data->referral_code
                    ));
                }
            }

            /* ----------------------------- Send Notification to Newly Registered the User ---------------------------- */
            $user->notify(new NewUserRegistered($user));

            Log::channel('daily')->info('REGISTER: END', [
                "uid" => $data->uuid,
                "response" => [
                    'message' => 'You have successfully registered!',
                    'user' => $user,
                ],
            ]);

            return $user;
        });
    }
}
