<?php

namespace App\Listeners\User\Transactions;

use App\Events\User\Transactions\TransferSuccessful;
use App\Models\User\Wallet;
use App\Notifications\User\Transactions\CreditAlertNotification;
use App\Notifications\User\Transactions\TransferSuccessfulNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferSuccessfulListener implements ShouldQueue
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
    public function handle(TransferSuccessful $event): void
    {
        $transaction = $event->transaction;
        $account_number = $event->account_number;
        $currency = $event->currency;
        $name = $event->name;

        try {
            DB::beginTransaction();
            Log::info("TransferSuccessfulListener.handle() - Starting transfer for transaction: {$transaction->id}, account_number: {$account_number}, currency: {$currency}");

            $wallet = $transaction->wallet;
            $user = $transaction->user;

            Log::info("TransferSuccessfulListener.handle() - Processing transfer for user: {$user->id}, transaction: {$transaction}");

            $recipient_wallet = Wallet::whereHas('virtualBankAccount', function ($query) use ($account_number, $currency) {
                $query->where([
                    ['account_number', $account_number],
                    ['currency', $currency],
                ]);
            })->where('currency', $currency)
                ->with(['user'])
                ->first();

            if (!is_null($recipient_wallet)) {
                $recipient_wallet->user->notify(new CreditAlertNotification($transaction, $user->name));
            }

            $this->transactionService->updateTransactionStatus(
                $transaction,
                'SUCCESSFUL',
            );
            $this->transactionService->updateTransactionStatus(
                $transaction->feeTransactions()->first(),
                'SUCCESSFUL',
            );

            Log::info("TransferSuccessfulListener.handle() - Updating wallet balance for user: {$user->id}, wallet: {$wallet->id}");
            
            $user->notify(new TransferSuccessfulNotification($transaction, $wallet, $name));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("TransferSuccessfulListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
