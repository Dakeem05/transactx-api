<?php

namespace App\Listeners\User\Wallet;

use App\Events\User\Wallet\WalletTransactionReceived;
use App\Models\Transaction;
use App\Models\User\Wallet;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserWalletWithTransactionListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public WalletService $walletService,
        public TransactionService $transactionService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WalletTransactionReceived $event): void
    {
        $account_number = $event->account_number;
        $amount = $event->amount;
        $currency = $event->currency;
        $external_reference = $event->external_reference;

        try {
            DB::beginTransaction();

            $wallet = Wallet::whereHas('virtualBankAccount', function ($query) use ($account_number, $currency) {
                $query->where([
                    ['account_number', $account_number],
                    ['currency', $currency],
                ]);
            })->where('currency', $currency)
                ->with(['user'])
                ->first();

            if (!$wallet) {
                Log::error('UpdateUserWalletWithTransactionListener.handle() - Wallet not found for account: ' . $account_number . ' and currency ' . $currency);
                return;
            }

            $user = $wallet->user;

            $this->walletService->deposit($wallet, $amount);

            $walletTransaction = $wallet->walletTransactions()->latest()->first();

            if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $amount) {
                Log::error('UpdateUserWalletWithTransactionListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }

            $transaction = $this->transactionService->createSuccessfulTransaction(
                $user,
                $amount,
                $wallet->id,
                $currency,
                'FUND_WALLET',
                $wallet->id,
                null,
                $external_reference,
            );

            // Associate wallet transaction
            $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("UpdateUserWalletWithTransactionListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
