<?php

namespace App\Notifications\User\Banking;

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

class ManualBankTransactionSyncNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $transactionAmount;
    protected float $transactionFee;
    protected string $transactionCurrency;
    protected float $walletAmount;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public Wallet $wallet
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->transactionFee = $this->transaction->feeTransactions()->first()->amount->getAmount()->toFloat();
        $this->transactionCurrency = $this->transaction->currency;
        $this->walletAmount = $this->wallet->amount->getAmount()->toFloat();
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
        return (new MailMessage)->subject('Manual Bank Transaction Sync')
            ->markdown(
                'email.user.banking.manual-transaction-sync',
                ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'manual-transaction-sync';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Manual Bank Transaction Sync";

        $body = "Your last manual bank transaction sync of successful and you were charged $this->transactionCurrency $this->transactionAmount.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'manual-transaction-sync',
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
            'title' => 'Manual Bank Transaction Sync',
            'message' => "Your last manual bank transaction sync of successful and you were charged $this->transactionCurrency $this->transactionAmount.",
            'data' => [
                'user_id' => $notifiable->id,
                'transaction' => $this->transaction,
                'wallet' => $this->wallet,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
