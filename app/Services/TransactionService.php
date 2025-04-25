<?php

namespace App\Services;

use App\Events\User\Wallet\FundWalletSuccessful;
use App\Models\User;
use App\Models\Transaction;
use App\Models\User\Wallet;
use Illuminate\Support\Str;
use App\Models\User\Wallet\WalletTransaction;

class TransactionService
{


    public function getTransactionDescription(string $type, string $currency): ?string
    {
        return match ($type) {
            'SEND_MONEY' => "Sent $currency",
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
        $userIp = null,
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        return Transaction::create([
            "user_id" => $user->id,
            "currency" => $currency,
            "amount" => $amount,
            "reference" => Str::uuid(),
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
            "user_ip" => $userIp,
        ]);
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

        if ($transaction->isSendMoneyTransaction()) {
            // Event
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
        $transactionAmount = $transaction->sender_local_amount->getMinorAmount()->toInt();

        // Due diligence check to ensure that the transaction originates from the wallet
        if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount) {
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
