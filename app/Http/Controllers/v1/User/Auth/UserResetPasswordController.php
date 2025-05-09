<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Resources\User\LoginUserResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserResetPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResetPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return TransactX::response(false, 'User not found', 404);
            }

            $verification_code = VerificationCode::where('email', $user->email)
                ->where('purpose', 'verification')
                ->where('is_verified', true)
                ->first();

            if (!$verification_code) {
                throw new InvalidArgumentException("Verification code has not been verified");
            }

            $verification_code->delete();

            $userService = resolve(UserService::class);
            $user = $userService->updateUserAccount($user, [
                'password' => $validatedData['password'],
            ]);

            return TransactX::response(true, 'Password changed successfully', 200);
        } catch (Exception $e) {
            Log::error('LOGIN USER: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
