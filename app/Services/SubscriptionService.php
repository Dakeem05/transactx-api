<?php

namespace App\Services;

use App\Enums\Subscription\ModelBillingEnum;
use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelStatusEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Business\SubscriptionModel;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\User\Subscription\SubscriptionExpiredNotification;
use App\Notifications\User\Subscription\SubscriptionRevertedNotification;
use App\Notifications\User\Subscription\SubscriptionUpgradeNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    public function fetchSubscriptionMethods()
    {
        return ModelPaymentMethodEnum::cases();
    }
    
    public function fetchUserSubscription(User $user)
    {
        return $user->subscription;
    }
    
    public function createSubscription(User $user, SubscriptionModel $model): Subscription
    {
        // Check if the user already has an active subscription
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('subscription_model_id', $model->id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            throw new InvalidArgumentException("User already has an active subscription for this model.");
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_model_id' => $model->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'renewal_date' => now()->addMonth(),
            'status' => ModelUserStatusEnum::ACTIVE,
        ]);

        return $subscription->fresh();
    }

    public function upgradeUserSubscription(User $user, array $data)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("User does not have an active subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'subscription_model_id' => $data['start'] === 'IMMEDIATE' ? $data['subscription_model_id'] : $subscription->subscription_model_id,
                'next_subscription_model_id' => $data['start'] === 'IMMEDIATE' ? null : $data['subscription_model_id'],
                'start_date' => $data['start'] === 'IMMEDIATE' ? now() : $subscription->start_date,
                'end_date' => $data['start'] === 'IMMEDIATE' ? ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()) : $subscription->end_date,
                'renewal_date' => $data['start'] === 'IMMEDIATE' ? ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()) : $subscription->renewal_date,
                'cancelled_at' => null,
                'status' => ModelUserStatusEnum::PENDING,
                'billing' => $data['billing'] == 'ANNUAL' ? ModelBillingEnum::ANNUAL : ModelBillingEnum::MONTHLY,
            ]);
            
            if ($data['start'] === 'IMMEDIATE') {
                $this->processPayment($user, $subscription, $data);
            } else {
               $user->notify(new SubscriptionUpgradeNotification(SubscriptionModel::find('subscription_model_id')));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to upgrade subscription: " . $e->getMessage());
        }

    }

    public function subscribe(User $user, array $data)
    {
        // Check if the user already has an active subscription
        $existingSubscription = $user->subscription;
        $free_subscription = SubscriptionModel::where('serial', 1)
                ->where('status', ModelStatusEnum::ACTIVE)
                ->first();

        if (!is_null($existingSubscription) && $existingSubscription->status == ModelUserStatusEnum::ACTIVE && $existingSubscription->subscription_model_id !== $free_subscription->id) {
            throw new InvalidArgumentException("User already has an active subscription for this model.");
        }

        try {
            DB::beginTransaction();

            $existingSubscription->update([
                'subscription_model_id' => $data['subscription_model_id'],
                'start_date' => now(),
                'end_date' => ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()),
                'renewal_date' => ($data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth()),
                'cancelled_at' => null,
                'status' => ModelUserStatusEnum::PENDING,
                'billing' => $data['billing'] == 'ANNUAL' ? ModelBillingEnum::ANNUAL : ModelBillingEnum::MONTHLY,
            ]);

            $this->processPayment($user, $existingSubscription, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to subscribe: " . $e->getMessage());
        }
    }

    protected function processPayment(User $user, Subscription $subscription, array $data)
    {
        $model = $subscription->model;
        $amount = $data['billing'] == 'ANNUAL' ? $model->amount->multipliedby(12) : $model->amount;
        $narration = "Subscribed for " . ucfirst($model->name->value) . " plan";

        if ($data['method'] == 'WALLET') {
            $transactionService = resolve(TransactionService::class);
            $transactionService->subscribe($user, $user->wallet->virtualBankAccount, $model, $amount->getAmount()->toFloat(), $narration, false, $data);
        }
    }

    public function cancelSubscription(User $user)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("User does not have an active subscription.");
        }

        $subscription->update([
            'status' => ModelUserStatusEnum::CANCELLED,
            'cancelled_at' => now(),
            'is_auto_renew' => false,
        ]);
    }

    public function resumeSubscription(User $user)
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::CANCELLED) {
            throw new InvalidArgumentException("User does not have a cancelled subscription.");
        }

        $subscription->update([
            'status' => ModelUserStatusEnum::ACTIVE,
            'cancelled_at' => null,
            'is_auto_renew' => true,
        ]);
    }

    public function expiredSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("User does not have a subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'status' => ModelUserStatusEnum::EXPIRED,
            ]);

            $subscription->user->notify(new SubscriptionExpiredNotification($subscription->model));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to expire subscription: " . $e->getMessage());
        }
    }
    
    public function autoRenewSubscription(Subscription $subscription)
    {
        if (is_null($subscription) || !$subscription->is_auto_renew) {
            throw new \InvalidArgumentException('Auto renew is disabled for this subscription');
        }

        try {
            DB::beginTransaction();
                
            $subscription->update([
                'subscription_model_id' => !is_null($subscription->next_subscription_model_id) ? $subscription->next_subscription_model_id : $subscription->subscription_model_id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => $subscription->billing == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'renewal_date' => $subscription->billing == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'status' => ModelUserStatusEnum::PENDING,
            ]);

            $this->processPayment($subscription->user, $subscription, ['method' => $subscription->method]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to auto renew subscription: " . $e->getMessage());
        }
    }
    
    public function renewSubscription(User $user, array $data)
    {
        $subscription = $user->subscription;
        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::EXPIRED) {
            throw new InvalidArgumentException("User does not have an expired subscription.");
        }
        
        try {
            DB::beginTransaction();
            $subscription->update([
                'start_date' => $subscription->end_date,
                'end_date' => $subscription->end_date->addMonth(),
                'renewal_date' => $subscription->end_date->addMonth(),
            ]);
            
            $subscription->update([
                'subscription_model_id' => !is_null($subscription->next_subscription_model_id) ? $subscription->next_subscription_model_id : $subscription->subscription_model_id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => $data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'renewal_date' => $data['billing'] == 'ANNUAL' ? now()->addMonths(12) : now()->addMonth(),
                'status' => ModelUserStatusEnum::PENDING,
            ]);
            
            $this->processPayment($user, $subscription, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to renew subscription: " . $e->getMessage());
        }
    }

    public function revertSubscription(Subscription $subscription)
    {
        if (is_null($subscription)) {
            throw new InvalidArgumentException("User does not have a subscription.");
        }
    
        try {
            DB::beginTransaction();
            
            $free_subscription = SubscriptionModel::where('serial', 1)
                ->where('status', ModelStatusEnum::ACTIVE)
                ->first();
                
            $subscription->update([
                'subscription_model_id' => $free_subscription->id,
                'next_subscription_model_id' => null,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'renewal_date' => now()->addMonth(),
                'status' => ModelUserStatusEnum::ACTIVE,
            ]);
            
            $subscription->user->notify(new SubscriptionRevertedNotification($subscription->model));
            //delete the things needed, sub accounts, linked account etc
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to auto renew subscription: " . $e->getMessage());
        }
    }
}
