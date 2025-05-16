<?php

namespace App\Http\Controllers\v1\User\Account\SubAccount;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Account\UpdateSubAccountRequest;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
            $user = Auth::user();

            $subaccounts = $this->userService->getSubAccounts($user);

            return TransactX::response(true, 'Sub accounts retrieved successfully', 200, $subaccounts);
        } catch (Exception $e) {
            Log::error('GET SUB ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 500);
        }
    }

    /**
     * Update user account (profile)
     */
    public function update(UpdateSubAccountRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            $sub_account = Auth::user()->subAccounts->find($id);

            if (!$sub_account) {
                return TransactX::response(false, 'Sub account not found', 404);
            }
    
            $sub_account = $this->userService->updateSubAccount($sub_account, $validatedData);
            
            return TransactX::response(true, 'Sub account updated successfully', 200, (object) ["sub_account" => $sub_account]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to update user account', 500);
        }
    }
    
    /**
     * Delete a sub account
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $sub_account = Auth::user()->subAccounts->find($id);

            if (!$sub_account) {
                return TransactX::response(false, 'Sub account not found', 404);
            }

            $this->userService->deleteSubAccount($sub_account);

            return TransactX::response(true, 'Sub account deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE USER ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, $e->getMessage(), 400);
        }  catch (Exception $e) {
            Log::error('DELETE SUB ACCOUNT: Error Encountered: ' . $e->getMessage()); 
            return TransactX::response(false, 'Failed to delete sub account', 500);
        }
    }
}
