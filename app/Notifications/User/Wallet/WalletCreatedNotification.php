<?php

namespace App\Notifications\User\Wallet;

use App\Models\User\Wallet;
use App\Models\VirtualBankAccount;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class WalletCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Wallet $wallet,
        protected ?VirtualBankAccount $virtualBankAccount
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
        return (new MailMessage)->subject('Wallet Created Successfully 💼')
            ->markdown(
                'email.user.wallet.wallet-created',
                ['user' => $notifiable, 'wallet' => $this->wallet, 'virtualBankAccount' => $this->virtualBankAccount]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'wallet-created';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Wallet Created Successfully 💼";
        $currency = $this->wallet->currency;
        $body = "Your $currency wallet has been created successfully.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'wallet-created',
            ]);
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $currency = $this->wallet->currency;
        return [
            'title' => 'Wallet Created Successfully 💼',
            'message' => "Your $currency wallet has been created successfully.",
            'data' => [
                'email' => $notifiable->email,
                'wallet' => $this->wallet,
                'virtual_bank_account' => $this->virtualBankAccount,
            ]
        ];
    }
}
