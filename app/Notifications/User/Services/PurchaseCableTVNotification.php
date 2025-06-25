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

class PurchaseCableTVNotification
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
    protected string $company;
    protected string $package;

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
        $this->recipient = $this->transaction->payload['number'];
        $this->company = $this->transaction->payload['company'];
        $this->package = $this->transaction->payload['name'];
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
            return (new MailMessage)->subject('Cable TV Subcription Processing')
                ->markdown(
                    'email.user.services.cabletv-processing',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'company' => $this->company, 'package' => $this->package]
                );
        } else if ($this->status == "SUCCESSFUL")  {
            return (new MailMessage)->subject('Cable TV Subcription Successful')
                ->markdown(
                    'email.user.services.cabletv-successful',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'company' => $this->company, 'package' => $this->package]
                );
        } else {
            return (new MailMessage)->subject('Cable TV Subcription Reversed')
                ->markdown(
                    'email.user.services.cabletv-reversed',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'company' => $this->company, 'package' => $this->package]
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
        return 'cabletv';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        if ($this->status == "PENDING") {
            $title = "Cable TV Subcription Processing";

            $body = "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount is processing.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'cabletv',
                ]);
        } else if ($this->status == "SUCCESSFUL") {
            $title = "Cable TV Subcription Successful";

            $body = "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount is successful.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'cabletv',
                ]);
        } else {
            $title = "Cable TV Subcription Reversed";

            $body = "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount failed and your funds reversed.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'cabletv',
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
                'title' => 'Cable TV Subcription Processing',
                'message' => "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount is processing",
                'data' => [
                    'user_id' => $notifiable->id,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'recipient' => $this->recipient,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        } else if ($this->status == "SUCCESSFUL") {
            return [
                'title' => 'Cable TV Subcription Successful',
                'message' => "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount is successful",
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
                'title' => 'Cable TV Subcription Reversed',
                'message' => "Your $this->company subscription of $this->package to $this->recipient costing $this->transactionCurrency $this->transactionAmount failed and your funds reversed",
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
