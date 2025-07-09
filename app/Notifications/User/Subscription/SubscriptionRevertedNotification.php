<?php

namespace App\Notifications\User\Subscription;

use App\Models\Business\SubscriptionModel;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionRevertedNotification

 extends Notification implements ShouldQueue
{
    use Queueable;

    public $plan;
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SubscriptionModel $subscription_model,
    ) {
        $this->plan = ucfirst($subscription_model->name->value);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $pushNotification = $notifiable->push_in_app_notifications;

        $channels = ['mail', 'database'];

        if ($pushNotification) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Subscription Reverted')
            ->markdown(
                'email.user.subscription.subscription-reverted',
                ['user' => $notifiable, 'model' => $this->subscription_model]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'subscription-reverted';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Subscription Reverted";

        $body = "You have been reverted to the $this->plan subscription plan and your uncovered data deleted.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-reverted',
            ]);
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Subscription Reverted',
            'message' => "You have been reverted to the $this->plan subscription plan and your uncovered data deleted",
            'data' => [
                'user_id' => $notifiable->id,
                'subscription_model' => $this->subscription_model,
            ]
        ];
    }
}
