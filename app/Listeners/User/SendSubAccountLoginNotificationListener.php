<?php

namespace App\Listeners\User;

use App\Events\User\SubAccountLoggedInEvent;
use App\Notifications\User\SubAccountLoginNotification;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;


class SendSubAccountLoginNotificationListener implements ShouldQueue
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
    public function handle(SubAccountLoggedInEvent $event): void
    {
        $user = $event->user;
        $main_account = $event->main_account;
        $ip_address = $event->ip_address;
        $user_agent = $event->user_agent;

        $main_account->notify(new SubAccountLoginNotification($user, $ip_address, $user_agent));
    }
}
