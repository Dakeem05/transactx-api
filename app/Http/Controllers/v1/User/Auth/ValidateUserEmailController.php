<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ValidateUserEmailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'unique:users,email'],
            ]);

            if ($validator->fails()) {
                return TransactX::response($validator->errors(), 422);
            }

            return TransactX::response([
                'message' => 'Email address is available.'
            ], 200);
        } catch (Exception $e) {
            Log::error('VALIDATE USER EMAIL: Error Encountered: ' . $e->getMessage());

            return TransactX::response(['message' => $e->getMessage()], 500);
        }
    }
}
