<?php

namespace App\Listeners\User\Transactions;

use App\Events\User\Transactions\TransferFailed;
use App\Notifications\User\Transactions\TransferFailedNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferFailedListener implements ShouldQueue
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
    public function handle(TransferFailed $event): void
    {
        $transaction = $event->transaction;
        $name = $event->name;

        try {
            DB::beginTransaction();

            $wallet = $transaction->wallet;
            $user = $transaction->user;

            $this->walletService->deposit($wallet, $transaction->amount + $transaction->feeTransactions()->first()->amount);

            $this->transactionService->updateTransactionStatus(
                $transaction,
                'REVERSED',
            );

            $this->transactionService->updateTransactionStatus(
                $transaction->feeTransactions()->first(),
                'REVERSED',
            );
            
            $user->notify(new TransferFailedNotification($transaction, $wallet, $name));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("class TransferFailedListener implements ShouldQueue
.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
