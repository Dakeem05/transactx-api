<?php

namespace App\Listeners\User;

use App\Events\User\UserLoggedInEvent;
use App\Notifications\User\UserLoginNotification;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;


class SendUserLoginNotificationListener implements ShouldQueue
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
    public function handle(UserLoggedInEvent $event): void
    {
        $user = $event->user;
        $ip_address = $event->ip_address;
        $user_agent = $event->user_agent;

        $user->notify(new UserLoginNotification($ip_address, $user_agent));
    }
}
