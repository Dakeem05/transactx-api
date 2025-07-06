<?php

namespace App\Services;

use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Business\SubscriptionModel;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
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

    public function upgradeUserSubscription(User $user, array $data): Subscription
    {
        $subscription = $user->subscription;

        if (is_null($subscription) || $subscription->status !== ModelUserStatusEnum::ACTIVE) {
            throw new InvalidArgumentException("User does not have an active subscription.");
        }

        try {
            DB::beginTransaction();

            $subscription->update([
                'subscription_model_id' => $data['billing'] === 'IMMEDIATE' ? $data['subscription_model_id'] : $subscription->subscription_model_id,
                'next_subscription_model_id' => $data['billing'] === 'IMMEDIATE' ? null : $data['subscription_model_id'],
                'start_date' => $data['billing'] === 'IMMEDIATE' ? now() : $subscription->start_date,
                'end_date' => $data['billing'] === 'IMMEDIATE' ? now()->addMonth() : $subscription->end_date,
                'renewal_date' => $data['billing'] === 'IMMEDIATE' ? now()->addMonth() : $subscription->renewal_date,
                'status' => ModelUserStatusEnum::PENDING,
            ]);
            
            if ($data['billing'] === 'IMMEDIATE') {
                $this->processInitialPayment($subscription, $data);
            } else {
                //notify user
                
            }

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to upgrade subscription: " . $e->getMessage());
        }

    }

    public function buySubscription(User $user, SubscriptionModel $model, array $data = []): Subscription
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
            'status' => ModelUserStatusEnum::PENDING,
        ]);

        $this->processInitialPayment($subscription, $data);
        return $subscription->fresh();
    }

    protected function processInitialPayment(Subscription $subscription, array $paymentData)
    {
        // Implement payment gateway integration here
        // Example with Paystack:
        $payment = $subscription->payments()->create([
            'amount' => $subscription->model->amount,
            'currency' => 'NGN',
            'status' => 'pending',
            'payment_reference' => $paymentData['reference'] ?? null,
        ]);

        // Verify payment with payment gateway
        $this->verifyPayment($payment);
    }

    public function verifyPayment(SubscriptionPayment $payment)
    {
        // Implement payment verification logic
        // Update payment status and subscription accordingly
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

    public function renewSubscription(Subscription $subscription)
    {
        if (!$subscription->is_auto_renew) {
            throw new \Exception('Auto renew is disabled for this subscription');
        }

        $subscription->update([
            'start_date' => $subscription->end_date,
            'end_date' => $subscription->end_date->addMonth(),
            'renewal_date' => $subscription->end_date->addMonth(),
        ]);

        $this->processRenewalPayment($subscription);
    }
}
