<?php

namespace App\Console\Commands\User\Subscription;

use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class SubscriptionRenewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription-renew-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription command to renew expired subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptions = Subscription::where('serial', '!=', 1)
            ->where('status', ModelUserStatusEnum::EXPIRED)
            ->where('renewal_date', '<=', now())
            ->where('is_auto_renew', true)
            ->get();

        if(!$subscriptions->isEmpty()) {
            foreach ($subscriptions as $subscription) {
                $subscriptionService = resolve(SubscriptionService::class);
                $subscriptionService->autoRenewSubscription($subscription);
            }
        }
    }
}
