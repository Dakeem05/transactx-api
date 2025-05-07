<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Dtos\User\CreateUserDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Resources\User\CreateUserResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserRegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterUserRequest $request)
    {
        try {
            $user_data = CreateUserDto::from($request->validated());

            $data = RegisterUserAction::handle($user_data, $request);

            if ($data instanceof JsonResponse) {
                return $data;
            }

            return TransactX::response(true, 'Registration successful', 201, new CreateUserResource($data));
        } catch (Exception $e) {
            Log::error('REGISTER USER: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
