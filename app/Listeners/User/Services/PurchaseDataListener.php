<?php

namespace App\Listeners\User\Services;

use App\Events\User\Services\PurchaseData;
use App\Models\Transaction;
use App\Notifications\User\Services\PurchaseDataNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseDataListener implements ShouldQueue
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
    public function handle(PurchaseData $event): void
    {
        $wallet = $event->wallet;
        $amount = $event->amount;
        $status = $event->status;
        $currency = $event->currency;
        $reference = $event->reference;
        $external_reference = $event->external_reference;
        $payload = $event->payload;
        $fees = 0;

        try {
            DB::beginTransaction();
            $user = $wallet->user;
            
            $this->walletService->debit($wallet, $amount + $fees);
            
            $walletTransaction = $wallet->walletTransactions()->latest()->first();
            
            if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $amount + $fees) {
                Log::error('PurchaseDataListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }

            if ($status == "successful") {
                $transaction = $this->transactionService->createSuccessfulTransaction(
                    $user,
                    $wallet->id,
                    $amount,
                    $currency,
                    'DATA',
                    null,
                    $external_reference,
                    $payload,
                    $reference,
                );

                $feeTransaction = $this->transactionService->createSuccessfulFeeTransaction(
                    $user,
                    $wallet->id,
                    $fees,
                    $currency,
                    'DATA_FEE',
                    $transaction->id
                );
                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
                // Associate wallet transaction
                $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);
                
                DB::commit();
                $user->notify(new PurchaseDataNotification($transaction, $wallet));
            } else if ($status == "processing") {
                $transaction = $this->transactionService->createPendingTransaction(
                    $user,
                    $amount,
                    $currency,
                    'DATA',
                    $reference,
                    $payload,
                    $wallet->id,
                    null,
                    null,
                    $external_reference,
                );

                $feeTransaction = $this->transactionService->createPendingFeeTransaction(
                    $user,
                    $fees,
                    $currency,
                    'DATA_FEE',
                    $reference,
                    $wallet->id,
                    $transaction->id
                );
                $transaction->feeTransactions()->save($feeTransaction);
                $transaction = Transaction::where('id', $transaction->id)->with(['feeTransactions'])->first();
                // Associate wallet transaction
                $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);
    
                DB::commit();
                $user->notify(new PurchaseDataNotification($transaction, $wallet));
            }
            

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("PurchaseDataListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
