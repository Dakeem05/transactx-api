<?php

namespace App\Services\User;

use App\Events\User\Wallet\UserWalletCreated;
use App\Models\User\Wallet;
use InvalidArgumentException;

class WalletService
{

    /**
     * Get the wallet by ID.
     *
     * @param string $id
     * @return Wallet|null
     */
    public function getByID($id): ?Wallet
    {
        return Wallet::find($id);
    }


    /**
     * Get the user's wallet.
     *
     * @param string $userId
     * @param string $currency
     * @return Wallet|null
     */
    public function getUserWallet($userId, $currency = 'NGN'): ?Wallet
    {
        return Wallet::whereUserId($userId)
            ->whereCurrency($currency)
            ->first();
    }


    /**
     * Get the user's wallet and additional info
     *
     * @param string $userId
     * @param string $currency
     * @return Wallet|null
     */
    public function getUserWalletDeep($userId, $currency = 'NGN'): ?Wallet
    {
        return Wallet::with('virtualBankAccount')->whereUserId($userId)
            ->whereCurrency($currency)
            ->first();
    }


    /**
     * Create a wallet for the user.
     *
     * @param string $userId
     * @param string $currency
     * @return Wallet
     */
    public function createWallet($userId, $currency = 'NGN'): Wallet
    {

        $wallet = Wallet::create([
            'user_id' => $userId,
            'currency' => $currency,
            'amount' => 0,
        ]);

        event(new UserWalletCreated($wallet));

        return $wallet;
    }



    /**
     * Deactivate user's wallet - is_active = false
     */
    public function deactivateUserWallet(string $userId, string $currency = 'NGN')
    {
        $wallet = $this->getUserWallet($userId, $currency);

        if (is_null($wallet)) {
            throw new InvalidArgumentException("Could not find user $currency wallet");
        }

        $wallet->update([
            'is_active' => false
        ]);

        return $wallet->refresh();
    }



    /**
     * Activate user's wallet - is_active = true
     */
    public function activateUserWallet(string $userId, string $currency = 'NGN')
    {
        $wallet = $this->getUserWallet($userId, $currency);

        if (is_null($wallet)) {
            throw new InvalidArgumentException("Could not find user $currency wallet");
        }

        $wallet->update([
            'is_active' => true
        ]);

        return $wallet->refresh();
    }
}
