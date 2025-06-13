<?php

namespace App\Providers;

use App\Events\User\SubAccountLoggedInEvent;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferMoney;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\UserAccountUpdated;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserLoggedInEvent;
use App\Events\User\VirtualBankAccount\VirtualBankAccountCreated;
use App\Events\User\Wallet\FundWalletSuccessful;
use App\Events\User\Wallet\UserWalletCreated;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Listeners\Referral\SendNewReferralNotificationListener;
use App\Listeners\User\CreateDefaultUserAvatarListener;
use App\Listeners\User\CreateUserAsCustomerOnPaystack;
use App\Listeners\User\SendSubAccountLoginNotificationListener;
use App\Listeners\User\SendUserLoginNotificationListener;
use App\Listeners\User\SendWelcomeOnboardNotificationListener;
use App\Listeners\User\Transactions\TransferFailedListener;
use App\Listeners\User\Transactions\TransferMoneyListener;
use App\Listeners\User\Transactions\TransferSuccessfulListener;
use App\Listeners\User\VirtualBankAccount\CreateVirtualBankAccountListener;
use App\Listeners\User\Wallet\SendWalletCreatedNotificationListener;
use App\Listeners\User\Wallet\SendWalletFundingSuccessfulNotificationListener;
use App\Listeners\User\Wallet\UpdateUserWalletWithTransactionListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserCreatedEvent::class => [
            SendWelcomeOnboardNotificationListener::class,
            CreateDefaultUserAvatarListener::class,
            SendNewReferralNotificationListener::class,
        ],
        UserAccountUpdated::class => [
            CreateUserAsCustomerOnPaystack::class
        ],
        UserLoggedInEvent::class => [
            SendUserLoginNotificationListener::class,
        ],
        SubAccountLoggedInEvent::class => [
            SendSubAccountLoginNotificationListener::class,
        ],
        UserWalletCreated::class => [
            CreateVirtualBankAccountListener::class //This creates a VBA and fires VirtualBankAccountCreated which sends notification to user about wallet and VBA
        ],
        VirtualBankAccountCreated::class => [
            SendWalletCreatedNotificationListener::class
        ],
        WalletTransactionReceived::class => [
            UpdateUserWalletWithTransactionListener::class
        ],
        FundWalletSuccessful::class => [
            SendWalletFundingSuccessfulNotificationListener::class
        ],
        TransferMoney::class => [
            TransferMoneyListener::class
        ],
        TransferSuccessful::class => [
            TransferSuccessfulListener::class
        ],
        TransferFailed::class => [
            TransferFailedListener::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
