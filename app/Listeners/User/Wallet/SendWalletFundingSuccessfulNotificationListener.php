<?php

namespace App\Listeners\User\Wallet;

use App\Events\User\Wallet\FundWalletSuccessful;
use App\Notifications\User\Wallet\WalletFundingSuccessfulNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendWalletFundingSuccessfulNotificationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FundWalletSuccessful $event): void
    {
        try {
            $transaction = $event->transaction;
            $user = $transaction->user;
            $wallet = $transaction->wallet;

            $user->notify(new WalletFundingSuccessfulNotification($transaction, $wallet));
            // 
        } catch (Exception $e) {
            Log::error('SendWalletFundingSuccessfulNotificationListener.handle(): Error Encountered - ' . $e->getMessage());
        }
    }
}
