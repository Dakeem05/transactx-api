<?php

namespace App\Actions\Auth;

use App\Dtos\User\CreateSubAccountDto;
use App\Enums\UserAccountTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateSubAccountAction
{
    /**
     * Handle the registration process for a new user.
     *
     * @param CreateSubAccountDto $createSubAccountDto The data transfer object containing user information.
     * @param Request $request The request
     * @return User|null Returns the registered user object or null if an error occurs.
     */
    public static function handle(CreateSubAccountDto $createSubAccountDto, Request $request)
    {
        return DB::transaction(function () use ($createSubAccountDto, $request) {

            // Create user
            $user = User::create([
                'name' => $createSubAccountDto->name,
                'username' => $createSubAccountDto->username,
                'password' => $createSubAccountDto->password,
                'account_type' => UserAccountTypeEnum::sub,
                'main_account_id' => $request->user()->id,
                'role_id' => Role::getUserRoleId(),
                'status' => UserStatusEnum::NEW,
            ]);

            // event(new UserCreatedEvent($user));

            Log::channel('daily')->info('CREATE SUB ACCOUNT: END', [
                "uid" => $createSubAccountDto->request_uuid,
                "response" => [
                    'data' => $user,
                ],
            ]);

            return $user;
        });
    }
}
