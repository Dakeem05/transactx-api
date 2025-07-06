<?php

namespace App\Listeners\User\VirtualBankAccount;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Wallet\UserWalletCreated;
use App\Models\Settings;
use App\Services\Utilities\PaymentService;
use App\Services\VirtualBankAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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

        Log::info('CreateVirtualBankAccountListener', ['event' => $event, 'wallet' => $wallet, 'user' => $user]);
        
        $paymentService = resolve(PaymentService::class);
        $provider = $paymentService->getPaymentServiceProvider();
        
        // Proper type casting to ServiceProviderDto
        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }
        
        $currency = Settings::where('name', 'currency')->first()->value;
        if (!$currency) {
            throw new \Exception('Currency not found');
        }
        
        $virtualAccount = $this->virtualBankAccountService->getWalletVirtualBankAccountForProvider(
            $wallet->id, 
            $provider->name
        );

        Log::info('CreateVirtualBankAccountListener 2', ['virtualAccount' => $virtualAccount]);
        
        if (is_null($virtualAccount)) {
            $this->virtualBankAccountService->createVirtualBankAccount(
                $user, 
                $currency, 
                $wallet->id, 
                $provider->name,
                $event->bvn ?? null,
                $event->verification_id ?? null,
                $event->otp ?? null,
            );
        }
    }
}
