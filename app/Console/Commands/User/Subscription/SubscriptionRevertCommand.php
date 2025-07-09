<?php

namespace App\Console\Commands\User\Subscription;

use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class SubscriptionRevertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-revert-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to revert user subscription to free plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptions = Subscription::where('serial', '!=', 1)
            ->where('status', ModelUserStatusEnum::EXPIRED)
            ->where('end_date', '<=', now()->subDays(7))
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                $subscriptionService = resolve(SubscriptionService::class);
                $subscriptionService->revertSubscription($subscription);
            }
        }
    }
}
