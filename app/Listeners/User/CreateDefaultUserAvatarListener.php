<?php

namespace App\Listeners\User;

use App\Events\User\UserCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateDefaultUserAvatarListener implements ShouldQueue
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
    public function handle(UserCreatedEvent $event): void
    {
        $user = $event->completeUserRegistrationDto->user;
        $user->avatar = cloudinary()->upload($user->create_avatar())->getSecurePath();
        $user->save();
    }
}
