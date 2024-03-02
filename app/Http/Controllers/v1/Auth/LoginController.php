<?php

namespace App\Http\Controllers\v1\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Dtos\User\LoginUserDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Resources\User\LoginUserResource;
use Exception;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginUserRequest $request)
    {
        try {
            $login_data = LoginUserDto::from($request->validated());

            $data = LoginUserAction::handle($login_data, $request);

            return TransactX::response(new LoginUserResource($data), 200);
        } catch (Exception $e) {
            Log::error('LOGIN USER: Error Encountered: ' . $e->getMessage());

            return TransactX::response(['message' => $e->getMessage()], 500);
        }
    }
}
