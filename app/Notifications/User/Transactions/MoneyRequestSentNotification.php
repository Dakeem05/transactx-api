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

class MoneyRequestSentNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $transactionAmount;
    protected string $transactionCurrency;
    protected string $requesteeFirstName;
    protected string $requesteeLastName;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public string $requestee,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->transactionCurrency = $this->transaction->currency;
        $this->requesteeFirstName = explode(' ', $this->requestee)[0];
        $this->requesteeLastName = explode(' ', $this->requestee)[1];
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
        return (new MailMessage)->subject('Money Request Sent 💸')
            ->markdown(
                'email.user.transactions.money-request-sent',
                ['user' => $notifiable, 'transaction' => $this->transaction, 'requesteeFirstName' => $this->requesteeFirstName, 'requesteeLastName' => $this->requesteeLastName]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'money-request-sent';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Money Request Sent 💸";

        $body = "You have successfully requested an amount of $this->transactionCurrency $this->transactionAmount to $this->requesteeFirstName $this->requesteeLastName.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'money-request-sent',
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
            'title' => 'Money Request Sent 💸',
            'message' => "You have successfully requested an amount of $this->transactionCurrency $this->transactionAmount to $this->requesteeFirstName $this->requesteeLastName.",
            'data' => [
                'user_id' => $notifiable->id,
                'transaction' => $this->transaction,
                'requestee' => $this->requestee,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
