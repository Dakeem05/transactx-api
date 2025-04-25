<?php

namespace App\Listeners\Referral;

use App\Events\User\UserCreatedEvent;
use App\Notifications\Referral\NewReferralNotification;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendNewReferralNotificationListener implements ShouldQueue
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
        $referred_user = $event->user;
        $referred_by_user_id = $referred_user->referred_by_user_id;

        if (!$referred_by_user_id) {
            return;
        }

        $referrer_user = $this->userService->getUserById($referred_by_user_id);
        $referrer_user->notify(new NewReferralNotification($referred_user));
    }
}
