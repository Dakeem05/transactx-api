<?php

namespace App\Listeners\User\VirtualBankAccount;

use App\Events\User\Wallet\UserWalletCreated;
use App\Services\VirtualBankAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateVirtualBankAccountListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public VirtualBankAccountService $virtualBankAccountService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserWalletCreated $event): void
    {
        $wallet = $event->wallet;
        $user = $wallet->user;

        $currency = 'NGN';
        $provider = 'PAYSTACK';

        $virtualAccount = $this->virtualBankAccountService->getWalletVirtualBankAccountForProvider($wallet->id, $provider);

        if (!$virtualAccount) {
            $this->virtualBankAccountService->createVirtualBankAccount($user, $currency, $wallet->id, $provider);
        }
    }
}
