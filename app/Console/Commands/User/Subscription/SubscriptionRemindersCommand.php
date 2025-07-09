<?php

namespace App\Console\Commands\User\Subscription;

use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Subscription;
use App\Notifications\User\Subscription\SubscriptionReminderNotification;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class SubscriptionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-reminders-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to handle reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptions = Subscription::where('serial', '!=', 1)
            ->where('status', ModelUserStatusEnum::ACTIVE)
            ->where('status', ModelUserStatusEnum::CANCELLED)
            ->where('end_date', '<=', now()->addDays(3))
            ->where('end_date', '>=', now())
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                $subscription->user->notify(new SubscriptionReminderNotification($subscription, $subscription->model));
            }
        }
    }
}
