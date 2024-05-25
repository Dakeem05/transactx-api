<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

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
        return User::find($user_id)->first();
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
        ]);

        return $user->refresh();
    }
}
