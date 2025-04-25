<?php

namespace App\Listeners\User;

use App\Events\User\UserCreatedEvent;
use App\Notifications\Referral\NewReferralNotification;
use App\Notifications\User\WelcomeOnboardNotification;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendWelcomeOnboardNotificationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected UserService $userService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        $user = $event->user;

        $user->notify(new WelcomeOnboardNotification());
    }
}
