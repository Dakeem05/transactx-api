<?php

namespace App\Listeners\User\Services;

use App\Events\User\Services\PurchaseCableTV;
use App\Models\Transaction;
use App\Notifications\User\Services\PurchaseCableTVNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseCableTVListener implements ShouldQueue
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
    public function handle(PurchaseCableTV $event): void
    {
        $wallet = $event->wallet;
        $amount = $event->amount;
        $status = $event->status;
        $currency = $event->currency;
        $reference = $event->reference;
        $external_transaction_reference = $event->external_transaction_reference;
        $payload = $event->payload;
        $fees = 0;

        try {
            DB::beginTransaction();
            $user = $wallet->user;
            
            $this->walletService->debit($wallet, $amount + $fees);
            
            $walletTransaction = $wallet->walletTransactions()->latest()->first();
            
            if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $amount + $fees) {
                Log::error('PurchaseCableTVListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }

            if ($status == "successful") {
                $transaction = $this->transactionService->createSuccessfulTransaction(
                    $user,
                    $wallet->id,
                    $amount,
                    $currency,
                    'CABLETV',
                    null,
                    $external_transaction_reference,
                    $payload,
                    $reference,
                );

                $feeTransaction = $this->transactionService->createSuccessfulFeeTransaction(
                    $user,
                    $wallet->id,
                    $fees,
                    $currency,
                    'CABLETV_FEE',
                    $transaction->id
                );
                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
                // Associate wallet transaction
                $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);
                
                $user->notify(new PurchaseCableTVNotification($transaction, $wallet));
                DB::commit();
            } else if ($status == "processing") {
                $transaction = $this->transactionService->createPendingTransaction(
                    $user,
                    $amount,
                    $currency,
                    'CABLETV',
                    $reference,
                    $payload,
                    $wallet->id,
                    null,
                    null,
                    $external_transaction_reference,
                );

                $feeTransaction = $this->transactionService->createPendingFeeTransaction(
                    $user,
                    $fees,
                    $currency,
                    'CABLETV_FEE',
                    $reference,
                    $wallet->id,
                    $transaction->id
                );
                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
                // Associate wallet transaction
                $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);
    
                $user->notify(new PurchaseCableTVNotification($transaction, $wallet));
                DB::commit();
            }
            

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("PurchaseCableTVListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
