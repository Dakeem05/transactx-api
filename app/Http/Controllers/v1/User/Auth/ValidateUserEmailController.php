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
                $errors = $validator->errors()->toArray();
        
                // Get the first validation error message
                $firstError = collect($errors)->flatten()->first();

                return TransactX::response(false, $firstError, 500);
            }

            return TransactX::response(true, 'Email address is available.', 200);
        } catch (Exception $e) {
            Log::error('VALIDATE USER EMAIL: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
