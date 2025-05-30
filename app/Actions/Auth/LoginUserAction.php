<?php

namespace App\Actions\Auth;

use App\Dtos\User\LoginUserDto;
use App\Events\User\SubAccountLoggedInEvent;
use App\Events\User\UserLoggedInEvent;
use App\Helpers\TransactX;
use App\Models\User;
use App\Services\User\DeviceTokenService;
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

            $user = User::where('username', $loginUserDto->username)->orWhere('email', $loginUserDto->username)->first();

            if (!$user || !Hash::check($loginUserDto->password, $user->password)) {
                return TransactX::response(false, 'The provided credentials are incorrect.', 401);
            }

            // Force delete old tokens
            $user->tokens()->delete();

            $token = $user->createToken('UserToken')->plainTextToken;

            if (!$user->country) $user->saveCountryFromIP($request);

            // Save User Device Token
            if (!is_null($loginUserDto->fcm_device_token)) {
                $deviceTokenService = resolve(DeviceTokenService::class);
                $deviceTokenService->saveDistinctTokenForUser($user, $loginUserDto->fcm_device_token);
            }

            //$user->generate_referral_code(); TO BE CALLED AFTER UPDATING PROFILE

            $ip_address = explode(',', $request->header('X-Forwarded-For'))[0];

            $user_agent = $request->header('User-Agent');

            $user->updateLastLoggedInDevice($user_agent);

            if ($user->isMainAccount()) {
                event(new UserLoggedInEvent($user, $ip_address, $user_agent));
            } else {
                $main_account = $user->mainAccount;
                event(new SubAccountLoggedInEvent($user, $main_account, $ip_address, $user_agent));
            }

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
