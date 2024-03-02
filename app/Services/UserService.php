<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Generates a 6 character code
     * @return string $random_code
     */
    public function generate_random_code(): string
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
    public function get_user_by_ref_code(string $referral_code, $column)
    {
        return is_array($column) ?
            User::select($column)->where('referral_code', $referral_code)->first() :
            User::select($column)->where('referral_code', $referral_code)->first()->$column;
    }
}
