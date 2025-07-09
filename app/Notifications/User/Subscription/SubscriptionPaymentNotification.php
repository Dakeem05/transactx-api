<?php

namespace App\Notifications\User\Subscription;

use App\Models\Transaction;
use App\Models\User\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionPaymentNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    public $status;
    protected float $transactionAmount;
    protected float $transactionFee;
    protected string $transactionCurrency;
    protected float $walletAmount;
    protected string $plan;
    protected string $billed_at;
    protected string $renewal;

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
        $this->plan = $this->transaction->payload['plan'];
        $this->billed_at = $this->transaction->payload['billed_at'];
        $this->renewal = $this->transaction->payload['renewal'];
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
            return (new MailMessage)->subject('Subscription Processing')
                ->markdown(
                    'email.user.services.subscription-processing',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'plan' => $this->plan,  'billed_at' => $this->billed_at,  'renewal' => $this->renewal]
                );
        } else if ($this->status == "SUCCESSFUL")  {
            if ($this->renewal) {
                return (new MailMessage)->subject('Subscription Successful')
                    ->markdown(
                        'email.user.services.subscription-successful',
                        ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'plan' => $this->plan,  'billed_at' => $this->billed_at,  'renewal' => $this->renewal]
                    );
            } else {
                return (new MailMessage)->subject('Subscription Renewed')
                    ->markdown(
                        'email.user.services.subscription-renewed',
                        ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'plan' => $this->plan,  'billed_at' => $this->billed_at,  'renewal' => $this->renewal]
                    );
            }
        } else {
            return (new MailMessage)->subject('Subscription Reversed')
                ->markdown(
                    'email.user.services.subscription-reversed',
                    ['user' => $notifiable, 'wallet' => $this->wallet, 'transaction' => $this->transaction, 'plan' => $this->plan,  'billed_at' => $this->billed_at,  'renewal' => $this->renewal]
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
        if ($this->status == "PENDING") {
            return  'subscription-processing';
        } else if ($this->status == "SUCCESSFUL") {
            if ($this->renewal) {
                return 'subscription-successful';
            } else {
                return 'subscription-renewed';
            }
        } else {
            return 'subscription-reversed';
        }
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        if ($this->status == "PENDING") {
            $title = "Subscription Processing";

            $body = "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency is processing.";
            
            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'subscription-processing',
                ]);
        } else if ($this->status == "SUCCESSFUL") {
            if ($this->renewal) {
                $title = "Subscription Successful";

                $body = "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency is successful.";

                return CloudMessage::new()
                    ->withDefaultSounds()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ])
                    ->withData([
                        'notification_key' => 'subscription-successful',
                    ]);
                } else {
                    $title = "Subscription Renewed";
    
                    $body = "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency has been renewed.";
    
                    return CloudMessage::new()
                        ->withDefaultSounds()
                        ->withNotification([
                            'title' => $title,
                            'body' => $body,
                        ])
                        ->withData([
                            'notification_key' => 'subscription-renewed',
                        ]);
                }
        } else {
            $title = "Subscription Reversed";

            $body = "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency failed and your funds reversed.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'subscription-reversed',
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
                'title' => 'Subscription Processing',
                'message' => "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency is processing",
                'data' => [
                    'user_id' => $notifiable->id,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        } else if ($this->status == "SUCCESSFUL") {
            if ($this->renewal) {
                return [
                    'title' => 'Subscription Successful',
                    'message' => "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency is successful.",
                    'data' => [
                        'user_id' => $notifiable->id,
                        'transaction' => $this->transaction,
                        'wallet' => $this->wallet,
                        'event_at' => $this->currentDateTime,
                    ]
                ];
            } else {
                return [
                    'title' => 'Subscription Renewed',
                    'message' => "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency has been renewed.",
                    'data' => [
                        'user_id' => $notifiable->id,
                        'transaction' => $this->transaction,
                        'wallet' => $this->wallet,
                        'event_at' => $this->currentDateTime,
                    ]
                ];
            }
        } else {
            return [
                'title' => 'Subscription Reversed',
                'message' => "Your $this->plan plan subscription of $this->transactionAmount $this->transactionCurrency billed at $this->billed_at $this->transactionCurrency failed and your funds reversed",
                'data' => [
                    'user_id' => $notifiable->id,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        }
    }
}
