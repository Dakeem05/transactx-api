<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Dtos\User\LoginUserDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Resources\User\LoginUserResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserLoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginUserRequest $request)
    {
        try {
            $login_data = LoginUserDto::from($request->validated());

            $data = LoginUserAction::handle($login_data, $request);

            if ($data instanceof JsonResponse) {
                return $data;
            }

            return TransactX::response(true, 'Login successful', 200, new LoginUserResource($data));
        } catch (Exception $e) {
            Log::error('LOGIN USER: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
