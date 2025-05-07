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

class SubAccountLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected User $user,
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
        return (new MailMessage)->subject('TransactX Sub Account Login Notification 🔔')
            ->markdown(
                'email.user.sub-account-login',
                ['main_account' => $notifiable, 'user' => $this->user, 'user_agent' => $this->user_agent, 'ip_address' => $this->ip_address]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'sub-account-login';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "TransactX Sub Account Login Notification 🔔";
        $name = $this->user->name;

        $body = "Your sub account $name accessed TransactX mobile profile with $this->user_agent [$this->ip_address] at $this->currentDateTime";
        
        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'sub-account-login',
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
            'title' => 'Your Sub Account logged in to TransactX 🔔',
            'message' => "Your sub account $this->user->name accessed TransactX mobile profile with $this->user_agent $this->ip_address at $this->currentDateTime",
            'data' => [
                'username' => $notifiable->username,
                'sub_account_name' => $this->user->name,
                'sub_account_username' => $this->user->username,
                'email' => $notifiable->email,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
