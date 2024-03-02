<?php

namespace App\Actions\Auth;

use App\Dtos\User\CreateUserDto;
use App\Dtos\User\CompleteUserRegistrationDto;
use App\Dtos\User\LoginUserDto;
use App\Enums\UserStatusEnum;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserLoggedInEvent;
use App\Helpers\TransactX;
use App\Jobs\User\CompleteUserRegistration;
use App\Models\Role;
use App\Models\User;
use Facades\App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginUserAction
{
    /**
     * Handle the registration process for a new user.
     *
     * @param LoginUserDto $loginUserDto The data transfer object containing user login information.
     * @param Request $request The request
     * @return array|JsonResponse Returns an array containing the user object and token or an error json response.
     */
    public static function handle(LoginUserDto $loginUserDto, Request $request): array|JsonResponse
    {
        return DB::transaction(function () use ($loginUserDto, $request) {

            $user = User::where('username', $loginUserDto->username)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return TransactX::response(['message' => 'The provided credentials are incorrect.'], 401);
            }

            // Force delete old tokens
            $user->tokens()->delete();

            $token = $user->createToken('UserToken')->plainTextToken;

            //CREATE FCM TOKEN

            if (!$user->country) $user->save_country_from_ip($request);

            //$user->generate_referral_code(); TO BE CALLED AFTER UPDATING PROFILE

            $ip_address = explode(',', $request->header('X-Forwarded-For'))[0];

            $user_agent = $request->header('User-Agent');

            $user->update_last_logged_in_device($user_agent);

            event(new UserLoggedInEvent($user, $ip_address, $user_agent));

            Log::channel('daily')->info('LOGIN: END', [
                "uid" => $loginUserDto->request_uuid,
                "response" => [
                    'data' => $user,
                ],
            ]);

            return ['user' => $user, 'token' => $token];
        });
    }
}
