<?php

namespace App\Services\User;

use App\Events\User\Wallet\UserWalletCreated;
use App\Models\Settings;
use App\Models\User\Wallet;
use App\Models\User\Wallet\WalletTransaction;
use App\Services\VirtualBankAccountService;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
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



    /**
     * Withdraw money from the wallet.
     *
     * @param Wallet $wallet
     * @param int $amount
     * @return void
     */
    public function withdraw(Wallet $wallet, $amount)
    {
        DB::transaction(function () use ($wallet, $amount) {

            $type = 'DEBIT';

            $amount = Money::of($amount, $wallet->currency);

            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found. id: $wallet->id");
            }
            if ($wallet->amount->getCurrency() !== $amount->getCurrency()) {
                throw new \Exception("withdraw(): The currencies do not match. Wallet currency: {$wallet->amount->getCurrency()}, incoming amount currency: {$amount->getCurrency()}. User ID: {$wallet->user_id}, wallet->currency: {$wallet->currency}");
            }

            if ($wallet->amount->isLessThan($amount)) {
                throw new \Exception("Insufficient funds. ID: $wallet->id Current Balance: $wallet->amount Incoming: $amount");
            }

            $previous_amount = $wallet->amount;
            $wallet->amount = $wallet->amount->minus($amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'type' => $type,
                'previous_balance' => $previous_amount,
                'new_balance' => $wallet->amount,
                'amount_change' => $amount
            ]);
        });
    }



    /**
     * Deposit money into the wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @return void
     */
    public function deposit(Wallet $wallet, $amount)
    {
        DB::transaction(function () use ($wallet, $amount) {

            $type = 'CREDIT';

            $amount = Money::of($amount, $wallet->currency);

            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found. id: $wallet->id");
            }

            if ($wallet->amount->getCurrency() !== $amount->getCurrency()) {
                throw new \Exception("deposit(): The currencies do not match. Wallet currency: {$wallet->amount->getCurrency()}, incoming amount currency: {$amount->getCurrency()}. User ID: {$wallet->user_id}, wallet->currency: {$wallet->currency}");
            }

            $previous_amount = $wallet->amount;
            $wallet->amount = $wallet->amount->plus($amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'type' => $type,
                'previous_balance' => $previous_amount,
                'new_balance' => $wallet->amount,
                'amount_change' => $amount
            ]);
        });
    }

    public function destroy(Wallet $wallet)
    {
        $wallet = Wallet::find($wallet->id);

        if (is_null($wallet)) {
            return;
        }

        $virtualBankAccount = $wallet->virtualBankAccount;

        if (!is_null($virtualBankAccount)) {
            $virtualBankAccountService = resolve(VirtualBankAccountService::class);
            $virtualBankAccountService->destroy($virtualBankAccount);
        }

        return $wallet->delete();
    }
}
