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

class PurchaseDataNotification
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
    protected string $plan;
    protected string $validity;

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
        $this->plan = $this->transaction->payload['plan'];
        $this->validity = $this->transaction->payload['validity'];
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
            return (new MailMessage)->subject('Data Purchase Processing')
                ->markdown(
                    'email.user.services.data-processing',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'network' => $this->network, 'plan' => $this->plan, 'validity' => $this->validity]
                );
        } else if ($this->status == "SUCCESSFUL")  {
            return (new MailMessage)->subject('Data Purchase Successful')
                ->markdown(
                    'email.user.services.data-successful',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'network' => $this->network, 'plan' => $this->plan, 'validity' => $this->validity]
                );
        } else {
            return (new MailMessage)->subject('Data Purchase Reversed')
                ->markdown(
                    'email.user.services.data-reversed',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'recipient' => $this->recipient, 'network' => $this->network, 'plan' => $this->plan, 'validity' => $this->validity]
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
        return 'data';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        if ($this->status == "PENDING") {
            $title = "Data Purchase Processing";

            $body = "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount is processing.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'airtime',
                ]);
        } else if ($this->status == "SUCCESSFUL") {
            $title = "Data Purchase Successful";

            $body = "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount is successful.";

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
            $title = "Data Purchase Reversed";

            $body = "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount failed and your funds reversed.";

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
                'title' => 'Data Purchase Processing',
                'message' => "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount is processing",
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
                'title' => 'Data Purchase Successful',
                'message' => "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount is successful",
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
                'title' => 'Data Purchase Reversed',
                'message' => "Your $this->validity $this->network $this->plan data bundle purchase to $this->recipient costing $this->transactionCurrency $this->transactionAmount failed and your funds reversed",
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
