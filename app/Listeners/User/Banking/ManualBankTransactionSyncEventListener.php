<?php

namespace App\Listeners\User\Banking;

use App\Events\User\Banking\ManualBankTransactionSyncEvent;
use App\Models\Transaction;
use App\Notifications\User\Subscription\SubscriptionPaymentNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualBankTransactionSyncEventListener implements ShouldQueue
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
    public function handle(ManualBankTransactionSyncEvent $event): void
    {
        Log::info("ManualBankTransactionSyncEventListener event: " . json_encode($event));
        $wallet = $event->wallet;
        $amount = $event->amount;
        $currency = $event->currency;
        $reference = $event->reference;
        $external_transaction_reference = $event->external_transaction_reference;
        $narration = $event->narration ?? null;
        $payload = $event->payload;
        $fees = 0;

        try {
            DB::beginTransaction();
            $user = $wallet->user;
            
            $this->walletService->debit($wallet, $amount + $fees);
            
            $walletTransaction = $wallet->walletTransactions()->latest()->first();
            
            if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $amount + $fees) {
                Log::error('ManualBankTransactionSyncEventListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }
            
            $transaction = $this->transactionService->createPendingTransaction(
                $user,
                $amount,
                $currency,
                'TRANSACTION_SYNC',
                $reference,
                $payload,
                $wallet->id,
                $narration,
                null,
                $external_transaction_reference,
            );

            Log::info("ManualBankTransactionSyncEventListener pending transaction: " . json_encode($transaction));

            $feeTransaction = $this->transactionService->createPendingFeeTransaction(
                $user,
                $fees,
                $currency,
                'TRANSACTION_SYNC_FEE',
                $reference,
                $wallet->id,
                $transaction->id
            );

            $transaction->feeTransactions()->save($feeTransaction);
            $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
            // Associate wallet transaction
            $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ManualBankTransactionSyncEventListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
