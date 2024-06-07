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

class BVNVerificationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $status,
        protected array $payload
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
        $subject = $this->status === 'SUCCESSFUL' ? "Yay! BVN Verification Successful ✅" : "Oops! BVN Verification Failed ❌";

        return (new MailMessage)->subject($subject)
            ->markdown(
                'email.user.user-verification-status',
                ['user' => $notifiable, 'status' => $this->status, 'payload' => $this->payload]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'bvn-verification';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = $this->status === 'SUCCESSFUL' ? "Yay! BVN Verification Successful ✅" : "Oops! BVN Verification Failed ❌";

        $body = $this->status === 'SUCCESSFUL' ? "Your BVN verification is successful" : "Your BVN verification failed. Please try again. Reason: " . $this->payload["data"]["reason"];

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'bvn-verification',
            ]);
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->status === 'SUCCESSFUL' ? "Yay! BVN Verification Successful ✅" : "Oops! BVN Verification Failed ❌";

        $body = $this->status === 'SUCCESSFUL' ? "Your BVN verification is successful" : "Your BVN verification failed. Please try again. Reason: " . $this->payload["data"]["reason"];

        return [
            'title' => $title,
            'message' => $body,
            'data' => [
                'username' => $notifiable->username,
                'email' => $notifiable->email,
                'bvn_status' => $this->status,
                'payload' => $this->payload,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
