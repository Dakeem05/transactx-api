<?php

namespace App\Listeners\User\Banking;

use App\Enums\Subscription\ModelPaymentStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Events\User\Banking\ManualBankTransactionSyncSuccessfulEvent;
use App\Models\SubscriptionPayment;
use App\Notifications\User\Banking\ManualBankTransactionSyncNotification;
use App\Services\TransactionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualBankTransactionSyncSuccessfulEventListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public TransactionService $transactionService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ManualBankTransactionSyncSuccessfulEvent $event): void
    {
        $transaction = $event->transaction;

        try {
            DB::beginTransaction();

            $wallet = $transaction->wallet;
            $user = $transaction->user;

            $this->transactionService->updateTransactionStatus(
                $transaction,
                'SUCCESSFUL',
            );
            $this->transactionService->updateTransactionStatus(
                $transaction->feeTransactions()->first(),
                'SUCCESSFUL',
            );

            $user->notify(new ManualBankTransactionSyncNotification($transaction, $wallet));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ManualBankTransactionSyncSuccessfulEventListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
