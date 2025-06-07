<?php

namespace App\Listeners\User\Wallet;

use App\Events\User\Wallet\CreateWalletEvent;
use App\Notifications\User\Wallet\WalletCreatedNotification;
use App\Services\User\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateWalletEventListener implements ShouldQueue
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
    public function handle(CreateWalletEvent $event): void
    {
        $user = $event->user;
        
        $userId = $user->id;

        $walletService = resolve(WalletService::class);

        if ($walletService->getUserWallet($userId)) {
            return;
        }

        $walletService->createWallet($userId, $event->bvn, $event->verification_id, $event->otp);

    }
}
