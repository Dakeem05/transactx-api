<?php

namespace App\Notifications\User\Subscription;

use App\Models\Business\SubscriptionModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionUpgradeNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $plan;
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SubscriptionModel $subscription_model
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
        return (new MailMessage)->subject('Subscription Upgraded')
            ->markdown(
                'email.user.subscription.subscription-upgraded',
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
        return 'subscription-upgraded';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Subscription Reversed";

        $body = "You have upgraded to $this->plan plan subscription and will be activated at the end of your current subscription.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-upgraded',
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
            'title' => 'Subscription Reversed',
            'message' => "You have upgraded to $this->plan plan subscription and will be activated at the end of your current subscription",
            'data' => [
                'user_id' => $notifiable->id,
                'subscription_model' => $this->subscription_model,
            ]
        ];
    }
}
