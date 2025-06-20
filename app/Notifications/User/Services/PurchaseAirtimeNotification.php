<?php

namespace App\Notifications\User\Services;

use App\Models\Transaction;
use App\Models\User\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class PurchaseAirtimeNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    public $status;
    protected float $transactionAmount;
    protected float $transactionFee;
    protected string $transactionCurrency;
    protected float $walletAmount;
    protected string $recipient;
    protected string $network;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public Wallet $wallet,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->transactionFee = $this->transaction->feeTransactions()->first()->amount->getAmount()->toFloat();
        $this->transactionCurrency = $this->transaction->currency;
        $this->status = $this->transaction->status;
        $this->walletAmount = $this->wallet->amount->getAmount()->toFloat();
        $this->recipient = $this->transaction->payload['phone_number'];
        $this->network = $this->transaction->payload['network'];
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
        if ($this->status == "PENDING") {
            return (new MailMessage)->subject('Airtime Purchase Processing')
                ->markdown(
                    'email.user.services.airtime-processing',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'network' => $this->network]
                );
        } else {
            return (new MailMessage)->subject('Airtime Purchase Successful')
                ->markdown(
                    'email.user.services.airtime-successful',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'network' => $this->network]
                );
        }
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'airtime';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        if ($this->status == "PENDING") {
            $title = "Airtime Purchase Processing";

            $body = "Your $this->network airtime purchase of $this->transactionCurrency $this->transactionAmount to $this->recipient is processing.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'airtime',
                ]);
        } else {
            $title = "Airtime Purchase Successful";

            $body = "Your $this->network airtime purchase of $this->transactionCurrency $this->transactionAmount to $this->recipient is successful.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'airtime',
                ]);
        }
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->status == "PENDING") {
            return [
                'title' => 'Airtime Purchase Processing',
                'message' => "Your $this->network airtime purchase of $this->transactionCurrency $this->transactionAmount to $this->recipient is processing",
                'data' => [
                    'user_id' => $notifiable->id,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'recipient' => $this->recipient,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        } else {
            return [
                'title' => 'Airtime Purchase Successful',
                'message' => "Your $this->network airtime purchase of $this->transactionCurrency $this->transactionAmount to $this->recipient is successful",
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
}
