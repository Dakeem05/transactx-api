<?php

namespace App\Notifications\User\Banking;

use App\Models\LinkedBankAccount;
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

class BankAccountLinkedNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    public string $accountNumber;
    public string $bankName;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public LinkedBankAccount $account,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
        $this->accountNumber = $this->account->account_number;
        $this->bankName = $this->account->bank_name;
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
        return (new MailMessage)->subject('Bank Account Linked Successfully')
            ->markdown(
                'email.user.banking.bank-linked',
                ['user' => $notifiable, 'account' => $this->account]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'bank-linked';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Bank Account Linked Successfully";

        $body = "You have successfully linked $this->accountNumber of $this->bankName. Your transaction can now be synched";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'bank-linked',
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
            'title' => 'Bank Account Linked Successfully',
            'message' => "You have successfully linked $this->accountNumber of $this->bankName. Your transaction can now be synched",
            'data' => [
                'user_id' => $notifiable->id,
                'account' => $this->account,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
