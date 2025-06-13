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

class TransferSuccessfulNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $transactionAmount;
    protected float $transactionFee;
    protected string $transactionCurrency;
    protected float $walletAmount;
    protected string $recipientFirstName;
    protected string $recipientLastName;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public Wallet $wallet,
        public string $recipient
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->transactionFee = $this->transaction->feeTransactions()->first()->amount->getAmount()->toFloat();
        $this->transactionCurrency = $this->transaction->currency;
        $this->walletAmount = $this->wallet->amount->getAmount()->toFloat();
        $this->recipientFirstName = explode(' ', $this->recipient)[0];
        $this->recipientLastName = explode(' ', $this->recipient)[1];
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
        return (new MailMessage)->subject('Transfer Successful 💸')
            ->markdown(
                'email.user.transactions.transfer-successful',
                ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipientFirstName' => $this->recipientFirstName, 'recipientLastName' => $this->recipientLastName]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'transfer-successful';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Transfer Successful 💸";

        $body = "Your transfer of $this->transactionCurrency $this->transactionAmount to $this->recipientFirstName $this->recipientLastName is successful 💸. You were charged $this->transactionCurrency $this->transactionFee.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'transfer-successful',
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
            'title' => 'Transfer Successful 💸',
            'message' => "Your transfer of $this->transactionCurrency $this->transactionAmount to $this->recipientFirstName $this->recipientLastName is successful 💸. You were charged $this->transactionCurrency $this->transactionFee.",
            'data' => [
                'user_id' => $notifiable->id,
                'transaction' => $this->transaction,
                'wallet' => $this->wallet,
                'recipient' => $this->recipient,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
