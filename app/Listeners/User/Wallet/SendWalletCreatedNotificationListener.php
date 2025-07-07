<?php

namespace App\Listeners\User\Wallet;

use App\Events\User\VirtualBankAccount\VirtualBankAccountCreated;
use App\Notifications\User\Wallet\WalletCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendWalletCreatedNotificationListener implements ShouldQueue
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
    public function handle(VirtualBankAccountCreated $event): void
    {
        $virtualBankAccount = $event->virtualBankAccount;
        $wallet = $virtualBankAccount->wallet;
        $user = $wallet->user;
        Log::info('SendWalletCreatedNotificationListener', ['virtualBankAccount' => $virtualBankAccount]);

        $user->notify(new WalletCreatedNotification($wallet, $virtualBankAccount));
    }
}
