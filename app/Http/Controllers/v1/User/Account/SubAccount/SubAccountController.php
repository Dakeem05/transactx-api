<?php

namespace App\Http\Controllers\v1\User\Account\SubAccount;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SubAccountController extends Controller
{
    /**
     * Create a new UserAccountController instance.
     */
    public function __construct(
        protected UserService $userService,
    ) {
    }


    /**
     * Return the user account
     */
    public function show(): JsonResponse
    {
        try {
            $user = auth()->user();

            $subaccounts = $this->userService->getSubAccounts($user);

            return TransactX::response(true, 'Sub accounts retrieved successfully', 200, $subaccounts);
        } catch (Exception $e) {
            Log::error('GET SUB ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
