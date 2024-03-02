<?php

namespace App\Http\Controllers\v1\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Dtos\User\CreateUserDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterUserRequest $request)
    {
        try {
            $user_data = CreateUserDto::from($request->validated());

            $data = RegisterUserAction::handle($user_data);

            return TransactX::response(new UserResource($data), 201);
        } catch (Exception $e) {
            Log::error('REGISTER USER: Error Encountered: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
