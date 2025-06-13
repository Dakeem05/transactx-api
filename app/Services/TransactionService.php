<?php

namespace App\Services;

use App\Dtos\Utilities\PaymentProviderDto;
use App\Events\User\Transactions\TransferMoney;
use App\Events\User\Wallet\FundWalletSuccessful;
use App\Models\MoneyRequestStyles;
use App\Models\Settings;
use App\Models\User;
use App\Models\Transaction;
use App\Models\User\Wallet;
use Illuminate\Support\Str;
use App\Models\User\Wallet\WalletTransaction;
use App\Models\VirtualBankAccount;
use App\Notifications\User\Transactions\MoneyRequestReceivedNotification;
use App\Notifications\User\Transactions\MoneyRequestSentNotification;
use App\Services\External\SafehavenService;
use App\Services\User\WalletService;
use App\Services\Utilities\PaymentService;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function queryUsers(string $username, string $id) 
    {
        // Get users matching the username search
        $users = User::where('username', 'LIKE', '%' . $username . '%')->where('id', '!=', $id)->get();
    
        // Modify each user's data (e.g., hide sensitive fields or add computed fields)
        $modifiedUsers = $users->map(function ($user) {
            return [
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'account_number' => isset($user->wallet) ? $user->wallet->virtualBankAccount->account_number : null,
                'bank_name' => isset($user->wallet) ? $user->wallet->virtualBankAccount->bank_name : null,
                'account_name' => isset($user->wallet) ? $user->wallet->virtualBankAccount->account_name : null,
            ];
        });
    
        return $modifiedUsers;
    }

    public function sendMoneyToUsername(array $data, User $user, string $ip_address) 
    {
        $recipient = User::where('username', $data['username'])->first();

        $this->verifyTransaction($data, $user);

        if (is_null($recipient)) {
            throw new Exception('Recipient not found');
        }
        if (!$recipient->kycVerified()) {
            throw new Exception('Recipient account not verifed');
        }
        if (is_null($recipient->wallet)) {
            throw new Exception('Recipient does not have a wallet');
        }
        if (is_null($recipient->wallet->virtualBankAccount)) {
            throw new Exception('Recipient does not have a virtual bank account');
        }

        $paymentService = resolve(PaymentService::class);
        $provider = $paymentService->getPaymentServiceProvider();
        
        // Proper type casting to PaymentProviderDto
        if (!$provider instanceof PaymentProviderDto) {
            $provider = new PaymentProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }

        if ($provider->name === 'safehaven') {
            $safehavenService = resolve(SafehavenService::class);
            $resolvedAccount = $safehavenService->resolveAccount(
                $recipient->wallet->virtualBankAccount->account_number, 
                $recipient->wallet->virtualBankAccount->bank_code
            );
            if (isset($resolvedAccount['data']['account_name'])) {
                $recipient->wallet->virtualBankAccount->account_name = $resolvedAccount['data']['account_name'];
                $recipient->wallet->virtualBankAccount->save();
            } else {
                throw new Exception('Failed to resolve recipient account name');
            }
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $recipient->wallet->virtualBankAccount->account_number, $recipient->wallet->virtualBankAccount->bank_code, $resolvedAccount['data']['session_id'], $data['narration'] ?? null, $ip_address ?? null);
    }
    
    public function sendMoneyToEmail(array $data, User $user, string $ip_address) 
    {
        $recipient = User::where('email', $data['email'])->first();
        
        $this->verifyTransaction($data, $user);
        
        if (is_null($recipient)) {
            throw new Exception('Recipient not found');
        }
        if (!$recipient->kycVerified()) {
            throw new Exception('Recipient account not verifed');
        }
        if (is_null($recipient->wallet)) {
            throw new Exception('Recipient does not have a wallet');
        }
        if (is_null($recipient->wallet->virtualBankAccount)) {
            throw new Exception('Recipient does not have a virtual bank account');
        }
        
        $paymentService = resolve(PaymentService::class);
        $provider = $paymentService->getPaymentServiceProvider();
        
        // Proper type casting to PaymentProviderDto
        if (!$provider instanceof PaymentProviderDto) {
            $provider = new PaymentProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }

        if ($provider->name === 'safehaven') {
            $safehavenService = resolve(SafehavenService::class);
            $resolvedAccount = $safehavenService->resolveAccount(
                $recipient->wallet->virtualBankAccount->account_number, 
                $recipient->wallet->virtualBankAccount->bank_code
            );
            if (isset($resolvedAccount['data']['account_name'])) {
                $recipient->wallet->virtualBankAccount->account_name = $resolvedAccount['data']['account_name'];
                $recipient->wallet->virtualBankAccount->save();
            } else {
                throw new Exception('Failed to resolve recipient account name');
            }
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $recipient->wallet->virtualBankAccount->account_number, $recipient->wallet->virtualBankAccount->bank_code, $resolvedAccount['data']['session_id'], $data['narration'] ?? null, $ip_address ?? null);
    }
    
    public function sendMoney(array $data, User $user, string $ip_address) 
    {
        $this->verifyTransaction($data, $user);
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $data['account_number'], $data['bank_code'], $data['session_id'], $data['narration'] ?? null, $ip_address ?? null, $data['account_name'], $data['bank_name']);
    }
    
    private function transfer (VirtualBankAccount $virtualBankAccount, int $amount, string $account_number, string $bank_code, string $session_id, string $narration = null, string $ip_address = null, string $name = null, string $bank_name = null)
    {
        try {
            $reference = uuid_create();
            $currency = Settings::where('name', 'currency')->first()->value;
            
            $data = [
                'debit_account_number' => $virtualBankAccount->account_number,
                'amount' => $amount,
                'account_number' => $account_number,
                'bank_code' => $bank_code,
                'currency' => $currency,
                'narration' => $narration,
                'reference' => $reference,
                'session_id' => $session_id,
            ];
            
            $paymentService = resolve(PaymentService::class);
            $response = $paymentService->transfer($data);
            if (isset($response['statusCode']) && $response['statusCode'] != 200) {
                Log::error('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                return;
            }
            
            
            $recipient_wallet = Wallet::whereHas('virtualBankAccount', function ($query) use ($account_number, $currency) {
                $query->where([
                    ['account_number', $account_number],
                    ['currency', $currency],
                ]);
            })->where('currency', $currency)
            ->first();
            
            if (!is_null($recipient_wallet)) {
                $payload = [
                    'account_number' => $account_number,
                    'bank_code' => $bank_code,
                    'bank_name' => $recipient_wallet->virtualBankAccount->bank_name,
                    'account_name' => $recipient_wallet->virtualBankAccount->account_name,
                ];
                event(new TransferMoney($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['paymentReference'], $data['narration'], $ip_address, $recipient_wallet->virtualBankAccount->account_name, $payload));
            } else {
                $payload = [
                    'account_number' => $account_number,
                    'bank_code' => $bank_code,
                    'bank_name' => $bank_name,
                    'account_name' => $name,
                ];
                event(new TransferMoney($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['paymentReference'], $data['narration'], $ip_address, $name, $payload));
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    
    public function getRequestStyles ()
    {
        return MoneyRequestStyles::where('status', true)->select(['id', 'name', 'content', 'picture'])->get();
        
    }
    
    public function requestMoneyFromUsername(array $data, User $user, string $ip_address) 
    {
        $requestee = User::where('username', $data['username'])->first();
        $this->verifyRequest($data, $user);
        
        if (is_null($requestee)) {
            throw new Exception('Requestee not found');
        }
        if (!$requestee->kycVerified()) {
            throw new Exception('Requestee account not verifed');
        }
        if (is_null($requestee->wallet)) {
            throw new Exception('Requestee does not have a wallet');
        }
        if (is_null($requestee->wallet->virtualBankAccount)) {
            throw new Exception('Requestee does not have a virtual bank account');
        }
        
        return $this->request($data['amount'], $data['request_style_id'], $user, $requestee, $data['content'] ?? null, $ip_address ?? null);
    }

    public function requestMoneyFromEmail(array $data, User $user, string $ip_address) 
    {
        $requestee = User::where('email', $data['email'])->first();
        $this->verifyRequest($data, $user);
        
        if (is_null($requestee)) {
            throw new Exception('Requestee not found');
        }
        if (!$requestee->kycVerified()) {
            throw new Exception('Requestee account not verifed');
        }
        if (is_null($requestee->wallet)) {
            throw new Exception('Requestee does not have a wallet');
        }
        if (is_null($requestee->wallet->virtualBankAccount)) {
            throw new Exception('Requestee does not have a virtual bank account');
        }
        
        return $this->request($data['amount'], $data['request_style_id'], $user, $requestee, $data['content'] ?? null, $ip_address ?? null);
    }

    private function request (int $amount, string $request_style_id, User $user,  User $requestee, string $request_content = null, string $ip_address = null)
    {
        try {
            DB::beginTransaction();

            $currency = Settings::where('name', 'currency')->first()->value;
            
            $request_style = MoneyRequestStyles::find($request_style_id);

            if (is_null($request_style)) {
                throw new Exception('Invalid request style id provided');
            }
            
            $content = '';
            $amount = Money::of($amount, Settings::where('name', 'currency')->first()->value);

            if (strtolower($request_style->name) !== 'custom') {
                $values = [
                    'amount' => $amount->getAmount()->toFloat(),
                    'currency' => $currency
                ];
                
                $content = preg_replace_callback('/{{(\w+)}}/', 
                    function($matches) use ($values) {
                        return $values[$matches[1]] ?? $matches[0];
                    }, 
                    $request_style->content
                );
            } else {
                if (is_null($request_content)) {
                    throw new Exception('Request content must be provided for Custom style');
                }
                $content = $request_content;
            }

            $transaction = $this->createSuccessfulTransaction($user, $user->wallet->id, $amount, $currency, 'REQUEST_MONEY', $ip_address);

            $user->notify(new MoneyRequestSentNotification($transaction, $requestee->name));
            $requestee->notify(new MoneyRequestReceivedNotification($user->name, $content));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    private function verifyRequest(array $data, User $user)
    {
        if (is_null($user->wallet)) {
            throw new Exception('Create and fund your wallet');
        }

        $min_transfer = Settings::where('name', 'min_transaction')->first()->value;

        if ($data['amount'] < $min_transfer) {
            throw new Exception('Transaction amount is below minimum transaction');
        }
    }

    private function verifyTransaction (array $data, User $user)
    {
        if (is_null($user->wallet)) {
            throw new Exception('Create and fund your wallet');
        }

        $min_transfer = Settings::where('name', 'min_transaction')->first()->value;

        // Uncomment the following lines if you want to enforce minimum transfer amount
        // if ($data['amount'] < $min_transfer) {
        //     throw new Exception('Transaction amount is below minimum transaction');
        // }

        $walletService = resolve(WalletService::class);
        $potential_charges = 50;
        if (!$walletService->checkBalance($user->wallet, $data['amount'] + $potential_charges)) {
            throw new Exception('Insufficient balance for that transaction');
        }
    }

    public function getTransactionDescription(string $type, string $currency): ?string
    {
        return match ($type) {
            'SEND_MONEY' => "Sent $currency",
            'REQUEST_MONEY' => "Requested $currency",
            'FUND_WALLET' => "Funded $currency wallet",
            default => null,
        };
    }

    /**
     * Create and return a new pending transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY",
        $reference,
        $payload,
        $wallet_id,
        $narration = null,
        $userIp = null,
        $external_transaction_reference = null
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "amount" => $amount,
            "reference" => $reference,
            "payload" => $payload,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
            "narration" => $narration,
            "user_ip" => $userIp,
            "external_transaction_reference" => $external_transaction_reference,
        ]);
        
        return $transaction;
    }

    /**
     * Create and return a new pending fee transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingFeeTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY_FEE",
        $reference,
        $payload,
        $wallet_id,
        $principal_transaction_id,
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "principal_transaction_id" => $principal_transaction_id,
            "amount" => $amount,
            "reference" => $reference,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
        ]);
        
        return $transaction;
    }


    /**
     * Create and return a new successful transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param string $wallet_id
     * @param ?string $userIp
     * @param ?string $external_transaction_reference
     * 
     * @return Transaction
     */
    public function createSuccessfulTransaction(
        User $user,
        $wallet_id,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY",
        $userIp = null,
        $external_transaction_reference = null
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "wallet_id" => $wallet_id,
            "currency" => $currency,
            "amount" => $amount,
            "reference" => Str::uuid(),
            "external_transaction_reference" => $external_transaction_reference,
            "status" => "SUCCESSFUL",
            "type" => $type,
            "description" => $description,
            "user_ip" => $userIp,
        ]);

        if ($transaction->isFundWalletTransaction()) {
            event(new FundWalletSuccessful($transaction));
        }

        return $transaction;
    }



    /**
     * Update Transaction with associated Wallet Transaction
     *
     * @param Transaction $transaction
     * @param Wallet $wallet
     * @param string|null $walletTransactionId
     * @return void
     */
    public function attachWalletTransactionFor(Transaction $transaction, Wallet $wallet, ?string $walletTransactionId = null)
    {
        $walletTransaction = null;

        if (is_null($walletTransactionId)) {
            $walletTransaction = WalletTransaction::with('wallet')->latest()->first();
        } else {
            $walletTransaction = WalletTransaction::with('wallet')->find($walletTransactionId);
        }

        $walletTransactionAmountChange = $walletTransaction->amount_change->getMinorAmount()->toInt();
        $transactionAmount = $transaction->amount->getMinorAmount()->toInt();
        $feeAmount = $transaction->feeTransactions()->first()->amount->getMinorAmount()->toInt();

        // Due diligence check to ensure that the transaction originates from the wallet
        if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount + $feeAmount) {
            $this->updateTransaction($transaction, ['wallet_transaction_id' => $walletTransaction->id]);
        }
    }



    /**
     * Update a transaction with new data.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction, array $data)
    {
        // Check if 'status' is in the data array and remove it
        $status = null;
        if (isset($data['status'])) {
            $status = $data['status'];
            unset($data['status']);
        }

        $transaction->update([
            'external_transaction_reference' => $data['external_transaction_reference'] ?? $transaction->external_transaction_reference,
            'reference' => $data['reference'] ?? $transaction->reference,
            'wallet_id' => $data['wallet_id'] ?? $transaction->wallet_id,
            'description' => $data['description'] ?? $transaction->description,
            'wallet_transaction_id' => $data['wallet_transaction_id'] ?? $transaction->wallet_transaction_id,
        ]);

        if ($status !== null) {
            $this->updateTransactionStatus($transaction, $status);
        }

        return $transaction;
    }



    /**
     * Update a transaction's status.
     *
     * @param Transaction $transaction
     * @param string $status
     * @return Transaction
     */
    public function updateTransactionStatus(Transaction $transaction, $status)
    {

        if (!in_array($status, ["SUCCESSFUL", "FAILED", "PENDING", "PROCESSING", "REVERSED"])) {
            throw new \Exception("TransactionService.updateTransactionStatus(): Invalid status: $status.");
        }

        $oldTransactionStatus = $transaction->status;

        $transaction->update([
            'status' => $status,
        ]);

        if ($status === "SUCCESSFUL" && $oldTransactionStatus !== "SUCCESSFUL") {
            // transaction state is changing to successful
            if ($transaction->isFundWalletTransaction()) {
                // Event
            }

            if ($transaction->isSendMoneyTransaction()) {
                // Event
            }
        }

        return $transaction;
    }
}
