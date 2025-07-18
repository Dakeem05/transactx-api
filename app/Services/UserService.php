<?php

namespace App\Services;

use App\Events\User\UserAccountUpdated;
use App\Models\User;
use App\Notifications\User\BVNVerificationStatusNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Generates a 6 character code
     * @return string $random_code
     */
    public function generateRandomCode(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $random_code = '';
        for ($i = 0; $i < 6; $i++) {
            $random_code .= $characters[rand(0, $charactersLength - 1)];
        }
        return $random_code;
    }

    /**
     * Get user by referral_code
     * @param string $referral_code
     * @param mixed $column
     */
    public function getUserByRefCode(string $referral_code, $column)
    {
        return is_array($column) ?
            User::select($column)->where('referral_code', $referral_code)->first() :
            User::select($column)->where('referral_code', $referral_code)->first()?->$column;
    }


    /**
     * Find user by id
     * 
     * @param string $user_id
     * @return User|null
     */
    public function getUserById($user_id)
    {
        return User::find($user_id);
    }


    /**
     * Find user by customer code
     * 
     * @param string $customer_code
     * @return User|null
     */
    public function getUserByCustomerCode($customer_code)
    {
        return User::where('customer_code', $customer_code)->first();
    }


    /**
     * Update user account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return User
     */
    public function updateUserAccount($user, $attributes)
    {
        $user->update([
            'name' => $attributes['name'] ?? $user->name,
            'phone_number' => $attributes['phone_number'] ?? $user->phone_number,
            'username' => $attributes['username'] ?? $user->username,
            'customer_code' => $attributes['customer_code'] ?? $user->customer_code,
            'bvn_status' => $attributes['bvn_status'] ?? $user->bvn_status,
            'bvn' => $attributes['bvn'] ?? $user->bvn,
            'kyc_status' => $attributes['kyc_status'] ?? $user->kyc_status,
            'transaction_pin' => $attributes['transaction_pin'] ?? $user->transaction_pin,
            'password' => $attributes['password'] ?? $user->password,
            'avatar' => $attributes['avatar'] ?? $user->avatar,
            'email_verified_at' => $attributes['email_verified_at'] ?? $user->email_verified_at,
            'user_type' => $attributes['user_type'] ?? $user->user_type,
        ]);

        $user->refresh();

        event(new UserAccountUpdated($user));

        return $user;
    }


    /**
     * This is used to set the bvn_status of a user based off the data from Paystack
     * 
     */
    public function processCustomerIdentification(string $status, array $payload)
    {
        Log::channel('daily')->info('processCustomerIdentification: START', ['string' => $status, 'payload' => $payload]);

        $customer_code = $payload['data']['customer_code'];

        $bvn_status = match ($status) {
            'failed' => 'FAILED',
            'success' => 'SUCCESSFUL',
            default => 'PENDING',
        };

        $user = $this->getUserByCustomerCode($customer_code);

        $this->updateUserAccount($user, [
            'bvn_status' => $bvn_status
        ]);

        $user->notify(new BVNVerificationStatusNotification($bvn_status, $payload));

        Log::channel('daily')->info('processCustomerIdentification: END');
    }

    public function getSubAccounts($user)
    {
        return User::where('main_account_id', $user->id)->get();
    }

    /**
     * Update sub account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return User
     */
    public function updateSubAccount($user, $attributes)
    {
        $user->update([
            'name' => $attributes['name'] ?? $user->name,
            'username' => $attributes['username'] ?? $user->username,
            'password' => $attributes['password'] ?? $user->password,
        ]);

        $user->refresh();

        // event(new UserAccountUpdated($user));

        return $user;
    }

    /**
     * delete sub account
     * 
     * @param User|Authenticatable $user
     * @param array $attributes
     * @return bool
     */
    public function deleteSubAccount($user)
    {
        return $user->delete();
    }
}
