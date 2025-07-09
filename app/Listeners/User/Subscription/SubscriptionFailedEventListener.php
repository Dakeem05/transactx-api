<?php

namespace App\Listeners\User\Subscription;

use App\Enums\Subscription\ModelPaymentStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Events\User\Subscription\SubscriptionFailedEvent;
use App\Models\SubscriptionPayment;
use App\Notifications\User\Subscription\SubscriptionPaymentNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionFailedEventListener implements ShouldQueue
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
    public function handle(SubscriptionFailedEvent $event): void
    {
        $transaction = $event->transaction;

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

            $user->subscription->update([
                'status' => ModelUserStatusEnum::EXPIRED,
            ]);
            $subscription_payment = SubscriptionPayment::find($transaction->payload['subscription_payment_id']);
            $subscription_payment->update([
                'status' => ModelPaymentStatusEnum::FAILED
            ]);

            $user->notify(new SubscriptionPaymentNotification($transaction, $wallet));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("class TransferFailedListener implements SubscriptionFailedEventListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
