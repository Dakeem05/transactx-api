<?php

namespace App\Console\Commands\User\Subscription;

use App\Enums\Subscription\ModelStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Business\SubscriptionModel;
use App\Models\Subscription;
use App\Notifications\User\Subscription\SubscriptionRevertReminderNotification;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class SubscriptionRevertReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-revert-reminder-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to remind users that the subscription will revert to free plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptions = Subscription::where('serial', '!=', 1)
            ->where('status', ModelUserStatusEnum::EXPIRED)
            ->where('end_date', '<=', now()->subDay()) // Already expired
            ->where('end_date', '>=', now()->subDays(6))
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                $free_subscription = SubscriptionModel::where('serial', 1)
                ->where('status', ModelStatusEnum::ACTIVE)
                ->first();
                $subscription->user->notify(new SubscriptionRevertReminderNotification($free_subscription));
            }
        }
    }
}
