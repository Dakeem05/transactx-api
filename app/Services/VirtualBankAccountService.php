<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualBankAccount;
use App\Services\External\PaystackService;
use Exception;
use Illuminate\Support\Facades\Log;

class VirtualBankAccountService
{

    /**
     * Get the virtual account by ID.
     *
     * @param string $id
     * @return VirtualBankAccount|null
     */
    public function getByID($id): ?VirtualBankAccount
    {
        return VirtualBankAccount::find($id);
    }


    /**
     * Get the virtual account by walletId.
     *
     * @param string $walletId
     * @return VirtualBankAccount|null
     */
    public function getWalletVirtualBankAccount(string $walletId): ?VirtualBankAccount
    {
        return VirtualBankAccount::whereWalletId($walletId)->first();
    }


    /**
     * Get the virtual account by walletId and provider
     *
     * @param string $walletId
     * @param string $provider
     * @return VirtualBankAccount|null
     */
    public function getWalletVirtualBankAccountForProvider(string $walletId, string $provider): ?VirtualBankAccount
    {
        return VirtualBankAccount::where([
            ['wallet_id', $walletId],
            ['provider', $provider]
        ])->first();
    }


    /**
     * Save a Virtual Account
     */
    public function saveWalletVirtualBankAccount(
        string $walletId,
        string $bankAccountNumber,
        string $bankAccountName,
        string $bankName,
        ?string $bankCode,
        string $provider = 'PAYSTACK',
    ): VirtualBankAccount {
        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => 'NGN',
            'account_number' => $bankAccountNumber,
            'account_name' => $bankAccountName,
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'provider' => $provider
        ]);
    }


    /**
     * Create a virtual bank account for the user.
     *
     * @param User $user
     * @param string $currency
     * @param string $walletId
     * @param string $provider
     * 
     * @return ?VirtualBankAccount|void
     */
    public function createVirtualBankAccount(User $user, $currency = 'NGN', string $walletId, string $provider)
    {
        if ($provider == 'PAYSTACK') {
            $virtualBankAccount = $this->createVirtualBankAccountViaPaystack($user, $currency, $walletId, $provider);
            return $virtualBankAccount;
        } else {
            throw new Exception('Invalid provider supplied. Provider' . $provider);
        }
    }



    /**
     * This creates a virtual bank account using Paystack service
     * 
     * @param \App\Models\User $user
     * @param string $currency
     * @param string $walletId
     * @param string $provider
     * 
     * @return VirtualBankAccount|\Illuminate\Database\Eloquent\Model|void
     */
    private function createVirtualBankAccountViaPaystack(User $user, $currency, string $walletId, string $provider)
    {
        $paystackService = resolve(PaystackService::class);

        $response = $paystackService->createDVA($user->customer_code);

        if (isset($response['status']) && $response['status'] != true) {
            Log::error('createVirtualBankAccountViaPaystack: Failed to create DVA. Reason: ' . $response['message']);
            return;
        }

        $data = $response['data'];

        $accountNumber = $data['account_number'];
        $accountName = $data['account_name'];
        $bankName = $data['bank']['name'];
        $bankCode = $data['bank']['id'];

        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => $currency,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'provider' => $provider,
        ]);
    }
}
