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
        $currency = strtoupper($event->currency); // Ensure consistent case
        $external_reference = $event->external_reference;

        try {
            DB::beginTransaction();

            // Optimized wallet query
            $wallet = Wallet::with(['user', 'virtualBankAccount'])
                ->whereHas('virtualBankAccount', fn($q) => $q->where('account_number', $account_number))
                ->where('currency', $currency)
                ->first();

            if (!$wallet) {
                Log::error('Wallet not found', [
                    'account_number' => $account_number,
                    'currency' => $currency,
                    'event_data' => $event
                ]);
                return;
            }

            // Validate amount is numeric
            if (!is_numeric($amount)) {
                Log::error('Invalid amount received', [
                    'amount' => $amount,
                    'type' => gettype($amount)
                ]);
                return;
            }

            $user = $wallet->user;

            // Deposit to wallet
            $this->walletService->deposit($wallet, (float) $amount);

            // Get the created wallet transaction
            $walletTransaction = $wallet->walletTransactions()
                ->where('amount_change', $amount)
                ->latest()
                ->first();

            if (!$walletTransaction) {
                Log::error('Wallet transaction not found after deposit', [
                    'wallet_id' => $wallet->id,
                    'amount' => $amount
                ]);
                return;
            }

            // Create main transaction
            $transaction = $this->transactionService->createSuccessfulTransaction(
                $user,
                (float) $amount,
                $currency, // Make sure your service accepts currency codes
                'FUND_WALLET',
                $wallet->id,
                null,
                $external_reference,
            );

            // Associate wallet transaction
            $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);

            DB::commit();

            Log::info('Wallet funding processed successfully', [
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $external_reference
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Wallet funding failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_data' => $event
            ]);
        }
    }
}
