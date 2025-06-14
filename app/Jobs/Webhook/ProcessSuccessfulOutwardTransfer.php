<?php

namespace App\Jobs\Webhook;

use App\Models\Transaction;
use App\Models\User\Wallet;
use App\Notifications\User\Transactions\CreditAlertNotification;
use App\Notifications\User\Transactions\TransferSuccessfulNotification;
use App\Services\TransactionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSuccessfulOutwardTransfer implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Transaction $transaction,
        public string $account_number,
        public string $currency,
        public string $name,
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transaction = $this->transaction;
        $account_number = $this->account_number;
        $currency = $this->currency;
        $name = $this->name;

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

            $transactionService = resolve(TransactionService::class);
            $transactionService->updateTransactionStatus(
                $transaction,
                'SUCCESSFUL',
            );
            $transactionService->updateTransactionStatus(
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
