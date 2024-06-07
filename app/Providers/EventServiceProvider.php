<?php

namespace App\Providers;

use App\Events\User\UserAccountUpdated;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserLoggedInEvent;
use App\Events\User\VirtualBankAccount\VirtualBankAccountCreated;
use App\Events\User\Wallet\UserWalletCreated;
use App\Listeners\Referral\SendNewReferralNotificationListener;
use App\Listeners\User\CreateDefaultUserAvatarListener;
use App\Listeners\User\CreateUserAsCustomerOnPaystack;
use App\Listeners\User\SendUserLoginNotificationListener;
use App\Listeners\User\SendWelcomeOnboardNotificationListener;
use App\Listeners\User\VirtualBankAccount\CreateVirtualBankAccountListener;
use App\Listeners\User\Wallet\SendWalletCreatedNotificationListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        UserWalletCreated::class => [
            CreateVirtualBankAccountListener::class //This creates a VBA and fires VirtualBankAccountCreated which sends notification to user about wallet and VBA
        ],
        VirtualBankAccountCreated::class => [
            SendWalletCreatedNotificationListener::class
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
