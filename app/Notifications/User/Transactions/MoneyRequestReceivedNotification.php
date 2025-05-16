<?php

namespace App\Notifications\User\Transactions;

use App\Models\Transaction;
use App\Models\User;
use App\Models\User\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class MoneyRequestReceivedNotification

 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected string $requesterFirstName;
    protected string $requesterLastName;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $requester,
        public string $content,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
        $this->requesterFirstName = explode(' ', $this->requester)[0];
        $this->requesterLastName = explode(' ', $this->requester)[1];
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
        return (new MailMessage)->subject('Money Request Received')
            ->markdown(
                'email.user.transactions.money-request-received',
                ['user' => $notifiable, 'content' => $this->content]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'money-request-received';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Money Request Received";

        $body = $this->content;

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'money-request-received',
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
            'title' => 'Money Request Received',
            'message' => $this->content,
            'data' => [
                'user_id' => $notifiable->id,
                'requester' => $this->requester,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
