<?php

namespace App\Services;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\VirtualBankAccount\VirtualBankAccountCreated;
use App\Models\Settings;
use App\Models\User;
use App\Models\VirtualBankAccount;
use App\Services\External\FlutterwaveService;
use App\Services\External\PaystackService;
use App\Services\External\SafehavenService;
use App\Services\Utilities\PaymentService;
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
        string $provider = 'flutterwave',
    ): VirtualBankAccount {
        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => Settings::where('name', 'currency')->first()->value,
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
    public function createVirtualBankAccount(User $user, $currency = 'NGN', string $walletId, string $provider, ?string $bvn = null, ?string $verification_id = null, ?string $otp = null)
    {
        if ($provider == 'paystack') {
            $virtualBankAccount = $this->createVirtualBankAccountViaPaystack($user, $currency, $walletId, $provider);
            event(new VirtualBankAccountCreated($virtualBankAccount));
            return $virtualBankAccount;
        } else if ($provider == 'flutterwave') {
            $virtualBankAccount = $this->createVirtualBankAccountViaFlutterwave($user, $currency, $walletId, $provider);
            event(new VirtualBankAccountCreated($virtualBankAccount));
            return $virtualBankAccount;
        } else if ($provider == 'safehaven') {
            $virtualBankAccount = $this->createVirtualBankAccountViaSafehaven($user, $currency, $walletId, $provider, $bvn, $verification_id, $otp);
            event(new VirtualBankAccountCreated($virtualBankAccount));
            return $virtualBankAccount;
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
        $id = $data['id'];
        $bankCode = null;

        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => $currency,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'provider' => $provider,
            'account_reference' => $id
        ]);
    }

    /**
     * This creates a payout subaccount using Flutterwave service
     * 
     * @param \App\Models\User $user
     * @param string $currency
     * @param string $walletId
     * @param string $provider
     * 
     * @return VirtualBankAccount|\Illuminate\Database\Eloquent\Model|void
     */
    private function createVirtualBankAccountViaFlutterwave(User $user, $currency, string $walletId, string $provider)
    {
        $flutterwaveService = resolve(FlutterwaveService::class);

        $response = $flutterwaveService->createPSA($user, Settings::where('name', 'country')->first()->value);
        if (isset($response['status']) && $response['status'] != 'success') {
            Log::error('createVirtualBankAccountViaFlutterwave: Failed to create PSA. Reason: ' . $response['message']);
            return;
        }

        $data = $response['data'];

        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => $currency,
            'country' => Settings::where('name', 'country')->first()->value,
            'account_number' => $data['nuban'],
            'account_name' => $data['account_name'],
            'account_reference' => $data['account_reference'],
            'barter_id' => $data['barter_id'],
            'bank_name' => $data['bank_name'],
            'bank_code' => $data['bank_code'],
            'provider' => $provider,
        ]);
    }

    /**
     * This creates a payout subaccount using Safehaven service
     * 
     * @param \App\Models\User $user
     * @param string $currency
     * @param string $walletId
     * @param string $provider
     * @param string|null $bvn
     * @param string|null $verification_id
     * @param string|null $otp
     * 
     * @return VirtualBankAccount|\Illuminate\Database\Eloquent\Model|void
     */
    private function createVirtualBankAccountViaSafehaven(User $user, $currency, string $walletId, string $provider, string $bvn, string $verification_id, string $otp)
    {
        $safehavenService = resolve(SafehavenService::class);
        Log::info('Creating ISA for user: ' . $user->id . ' with BVN: ' . $bvn . ', verification_id: ' . $verification_id . ', otp: ' . $otp);

        $response = $safehavenService->createISA($user, Settings::where('name', 'country')->first()->value, $bvn, $verification_id, $otp);
        
        if (isset($response['statusCode']) && $response['statusCode'] != 200) {
            Log::error('createVirtualBankAccountViaSafehaven: Failed to create ISA. Reason: ' . $response['message']);
            return;
        }

        $data = $response['data'];

        return VirtualBankAccount::create([
            'wallet_id' => $walletId,
            'currency' => $currency,
            'country' => Settings::where('name', 'country')->first()->value,
            'account_number' => $data['accountNumber'],
            'account_name' => $data['accountName'],
            'account_reference' => $data['externalReference'],
            'barter_id' => $data['_id'],
            'bank_name' => 'Safehaven',
            'bank_code' => '090286', // Safehaven does not provide a bank code
            'provider' => $provider,
        ]);
    }

    public function getAccount(VirtualBankAccount $virtualBankAccount, $currency)
    {
        $virtualBankAccount = VirtualBankAccount::find($virtualBankAccount->id);

        if (is_null($virtualBankAccount)) {
            return;
        }

        $paymentService = resolve(PaymentService::class);
        $provider = $paymentService->getPaymentServiceProvider();
        
        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }

        $provider = $provider->name;

        if ($provider == 'paystack') {
            // $this->getVirtualBankAccountViaPaystack($virtualBankAccount->account_reference, $currency);
            throw new Exception("Getting of paystack balance not integrated.");
        } else if ($provider == 'flutterwave') {
            return $this->getVirtualBankAccountViaFlutterwave($virtualBankAccount->account_reference, $currency);
        } else if ($provider == 'safehaven') {
            return $this->getVirtualBankAccountViaSafehaven($virtualBankAccount->barter_id, $currency);
        }
    }

    private function getVirtualBankAccountViaFlutterwave(string $account_reference, string $currency)
    {
        $flutterwaveService = resolve(FlutterwaveService::class);

        $response = $flutterwaveService->getPSA($account_reference, $currency);
        if (isset($response['status']) && $response['status'] != 'success') {
            Log::error('getVirtualBankAccountViaFlutterwave: Failed to get PSA. Reason: ' . $response['message']);
            return;
        }

        return $response['data'];
    }

    private function getVirtualBankAccountViaSafehaven(string $account_reference, string $currency)
    {
        $safehavenService = resolve(SafehavenService::class);

        $response = $safehavenService->getISA($account_reference, $currency);
        if (isset($response['statusCode']) && $response['statusCode'] != 200) {
            Log::error('getVirtualBankAccountViaSafehaven: Failed to get ISA. Reason: ' . $response['message']);
            return;
        }

        return $response['data'];
    }

    public function destroy(VirtualBankAccount $virtualBankAccount)
    {
        $virtualBankAccount = VirtualBankAccount::find($virtualBankAccount->id);

        if (is_null($virtualBankAccount)) {
            return;
        }

        $paymentService = resolve(PaymentService::class);
        $provider = $paymentService->getPaymentServiceProvider();
        
        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }

        $provider = $provider->name;

        if ($provider == 'paystack') {
            $this->deleteVirtualBankAccountViaPaystack($virtualBankAccount->account_reference);
        } else if ($provider == 'flutterwave') {
            $this->deleteVirtualBankAccountViaFlutterwave($virtualBankAccount->account_reference);
        } 
        return $virtualBankAccount->delete();
    }

    private function deleteVirtualBankAccountViaPaystack(string $account_reference)
    {
        $paystackService = resolve(PaystackService::class);

        $response = $paystackService->deleteDVA($account_reference);

        if (isset($response['status']) && $response['status'] != true) {
            Log::error('deleteVirtualBankAccountViaPaystack: Failed to delete DVA. Reason: ' . $response['message']);
            return;
        }
        
        return;
    }

    private function deleteVirtualBankAccountViaFlutterwave(string $account_reference)
    {
        $flutterwaveService = resolve(FlutterwaveService::class);

        $response = $flutterwaveService->deletePSA($account_reference);
        if (isset($response['status']) && $response['status'] != 'success') {
            Log::error('deleteVirtualBankAccountViaFlutterwave: Failed to delete PSA. Reason: ' . $response['message']);
            return;
        }

        return;
    }
}
