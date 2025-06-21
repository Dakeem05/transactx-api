<?php

namespace App\Listeners\User\Services;

use App\Events\User\Services\PurchaseDataUpdate;
use App\Notifications\User\Services\PurchaseDataNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseDataUpdateListener implements ShouldQueue
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
    public function handle(PurchaseDataUpdate $event): void
    {
        $transaction = $event->transaction;
        $status = $event->status;
        try {
            DB::beginTransaction();

            $wallet = $transaction->wallet;
            $user = $transaction->user;

            if ($status == "successful") {
                $this->transactionService->updateTransactionStatus(
                    $transaction,
                    'SUCCESSFUL',
                );
                $this->transactionService->updateTransactionStatus(
                    $transaction->feeTransactions()->first(),
                    'SUCCESSFUL',
                );
    
                DB::commit();
                $user->notify(new PurchaseDataNotification($transaction, $wallet));
    
            } else if ($status == "processing") {
                $user->notify(new PurchaseDataNotification($transaction, $wallet));
            } else {
                $this->walletService->deposit($wallet, $transaction->amount->getAmount()->toFloat() + $transaction->feeTransactions()->first()->amount->getAmount()->toFloat());

                $walletTransaction = $wallet->walletTransactions()->latest()->first();

                if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $transaction->amount->getAmount()->toFloat() + $transaction->feeTransactions()->first()->amount->getAmount()->toFloat()) {
                    Log::error('PurchaseDataUpdateListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                    return;
                }

                $this->transactionService->updateTransactionStatus(
                    $transaction,
                    'REVERSED',
                );
    
                $this->transactionService->updateTransactionStatus(
                    $transaction->feeTransactions()->first(),
                    'REVERSED',
                );
                DB::commit();
                $user->notify(new PurchaseDataNotification($transaction, $wallet));
            }
            

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("PurchaseDataUpdateListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
