<?php

namespace App\Services;

use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Business\SubscriptionModel;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SubscriptionService
{
    public function fetchSubscriptionModels()
    {
        // Fetch all subscription models
        return SubscriptionModel::where('status', ModelUserStatusEnum::ACTIVE)->get();
    }
    
    public function getSubscriptionMethods()
    {
        return ModelPaymentMethodEnum::cases();
    }
    
    public function createSubscription(User $user, SubscriptionModel $model, array $data): Subscription
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

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
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
