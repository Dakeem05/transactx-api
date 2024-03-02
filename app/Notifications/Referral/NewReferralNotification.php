<?php

namespace App\Notifications\Referral;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReferralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected User $referred_user
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable->push_in_app_notifications && $notifiable->push_email_notifications) {
            return ['mail', 'database'];
        }

        return $notifiable->push_in_app_notifications ? ['mail', 'database'] : ($notifiable->push_email_notifications ? ['mail'] : ['mail', 'database']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Yay! Someone joined TransactX 🫶')
            ->markdown(
                'email.user.new-referral',
                ['user' => $notifiable, 'referred_user' => $this->referred_user]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'new-referral';
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Referral',
            'message' => "Yay! Someone joined TransactX using your referral code 🫶.",
            'data' => [
                'email' => $this->referred_user->email,
                'username' => $this->referred_user->username,
                'joined_at' => $this->referred_user->created_at,
            ]
        ];
    }
}
