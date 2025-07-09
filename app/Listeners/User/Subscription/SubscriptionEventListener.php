<?php

namespace App\Listeners\User\Subscription;

use App\Events\User\Subscription\SubscriptionEvent;
use App\Models\Transaction;
use App\Notifications\User\Subscription\SubscriptionPaymentNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionEventListener implements ShouldQueue
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
    public function handle(SubscriptionEvent $event): void
    {
        Log::info("SubscriptionListener event: " . json_encode($event));
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
                Log::error('SubscriptionListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }
            
            $transaction = $this->transactionService->createPendingTransaction(
                $user,
                $amount,
                $currency,
                'SUBSCRIPTION',
                $reference,
                $payload,
                $wallet->id,
                $narration,
                null,
                $external_transaction_reference,
            );

            Log::info("SubscriptionListener pending transaction: " . json_encode($transaction));

            $feeTransaction = $this->transactionService->createPendingFeeTransaction(
                $user,
                $fees,
                $currency,
                'SUBSCRIPTION_FEE',
                $reference,
                $wallet->id,
                $transaction->id
            );

            $transaction->feeTransactions()->save($feeTransaction);
            $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
            // Associate wallet transaction
            $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);

            $user->notify(new SubscriptionPaymentNotification($transaction, $wallet));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SubscriptionListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
