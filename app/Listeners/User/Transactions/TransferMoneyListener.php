<?php

namespace App\Listeners\User\Transactions;

use App\Events\User\Transactions\TransferMoney;
use App\Notifications\User\Transactions\TransferMoneyNotification;
use App\Services\TransactionService;
use App\Services\User\WalletService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferMoneyListener implements ShouldQueue
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
    public function handle(TransferMoney $event): void
    {
        $wallet = $event->wallet;
        $amount = $event->amount;
        $currency = $event->currency;
        $reference = $event->reference;
        $external_reference = $event->external_reference;
        $narration = $event->narration ?? null;
        $ip_address = $event->ip_address ?? null;
        $name = $event->name;
        $payload = $event->payload;

        try {
            DB::beginTransaction();
            
            $user = $wallet->user;
            
            $this->walletService->debit($wallet, $amount);
            
            $walletTransaction = $wallet->walletTransactions()->latest()->first();
            
            if (!$walletTransaction && $walletTransaction->wallet_id != $wallet->id && $walletTransaction->amount_change != $amount) {
                Log::error('TransferMoneyListener.handle() - Could not find matching transaction for wallet: ' . $wallet->id);
                return;
            }
            
            $transaction = $this->transactionService->createPendingTransaction(
                $user,
                $amount,
                $currency,
                'SEND_MONEY',
                $reference,
                $payload,
                $wallet->id,
                $narration,
                $ip_address,
                $external_reference,
            );
            
            // Associate wallet transaction
            $this->transactionService->attachWalletTransactionFor($transaction, $wallet, $walletTransaction->id);

            
            $user->notify(new TransferMoneyNotification($transaction, $wallet, $name));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("TransferMoneyListener.handle() - Error Encountered - " . $e->getMessage());
            throw $e;
        }
    }
}
