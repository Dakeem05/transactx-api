<?php

namespace App\Notifications\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class UserLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $ip_address,
        protected string $user_agent
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
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
        return (new MailMessage)->subject('TransactX Account Login Notification 🔔')
            ->markdown(
                'email.user.user-login',
                ['user' => $notifiable, 'user_agent' => $this->user_agent, 'ip_address' => $this->ip_address]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'user-login';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "TransactX Account Login Notification 🔔";

        $body = "You accessed your TransactX mobile profile with $this->user_agent [$this->ip_address] at $this->currentDateTime";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'user_login',
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
            'title' => 'You logged in to TransactX 🔔',
            'message' => "You accessed your TransactX mobile profile with $this->user_agent $this->ip_address at $this->currentDateTime",
            'data' => [
                'username' => $notifiable->username,
                'email' => $notifiable->email,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
