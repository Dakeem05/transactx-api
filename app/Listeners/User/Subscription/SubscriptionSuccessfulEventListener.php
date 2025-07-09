<?php

namespace App\Listeners\User\Subscription;

use App\Enums\Subscription\ModelPaymentStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Events\User\Subscription\SubscriptionSuccessfulEvent;
use App\Models\SubscriptionPayment;
use App\Notifications\User\Subscription\SubscriptionPaymentNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionSuccessfulEventListener implements ShouldQueue
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
    public function handle(SubscriptionSuccessfulEvent $event): void
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

            $user->subscription->update([
                'status' => ModelUserStatusEnum::ACTIVE,
            ]);
            $subscription_payment = SubscriptionPayment::find($transaction->payload['subscription_payment_id']);
            $subscription_payment->update([
                'status' => ModelPaymentStatusEnum::SUCCESSFUL
            ]);

            $user->notify(new SubscriptionPaymentNotification($transaction, $wallet));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SubscriptionSuccessfulListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
