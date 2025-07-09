<?php

namespace App\Jobs;

use App\Enums\PartnersEnum;
use App\Events\User\Subscription\SubscriptionFailedEvent;
use App\Events\User\Subscription\SubscriptionSuccessfulEvent;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Jobs\Webhook\ProcessSuccessfulOutwardTransfer;
use App\Models\Settings;
use App\Models\Transaction;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSafehavenWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        protected array $payload,
        protected string $ipAddress
    ) {}

    public function handle(WebhookService $webhookService)
    {
        try {
            Log::info('Processing Safehaven webhook in queue', ['payload' => $this->payload]);

            $responseData = ['message' => 'Webhook processed'];
            $webhookService->recordIncomingWebhook(
                PartnersEnum::SAFEHAVEN->value,
                $this->payload,
                $responseData,
                200,
                $this->ipAddress
            );

            $event_type = $this->payload['type'] ?? null;

            // Payout subaccount funding webhook
            if (in_array($event_type, ['transfer']) && $this->payload['data']['type'] === 'Inwards' && $this->payload['data']['status'] === 'Completed') {
                $this->processInwardTransfer();
                return;
            }

            // Successful transfers webhook
            if (in_array($event_type, ['transfer']) && $this->payload['data']['type'] === 'Outwards' && in_array($this->payload['data']['status'], ['Created', 'Completed']) && !$this->payload['data']['isReversed']) {
                $this->processSuccessfulOutwardTransfer();
                $this->processSuccessfulSubscription();
                return;
            }
            
            // Failed transfers webhook
            if (in_array($event_type, ['transfer']) && $this->payload['data']['type'] === 'Outwards' && $this->payload['data']['isReversed']) {
                $this->processFailedTransfer();
                $this->processFailedSubscription();
                return;
            }

        } catch (\Exception $e) {
            Log::error('Safehaven Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);
            throw $e;
        }
    }

    protected function processInwardTransfer()
    {
        $external_transaction_reference = $this->payload['data']['paymentReference'] ?? $this->payload['data']['sessionId'];
        $account_number = $this->payload['data']['creditAccountNumber'];
        $amount = $this->payload['data']['amount'];
        $fees = $this->payload['data']['fees'];
        $currency = Settings::where('name', 'currency')->first()->value;
        
        Log::info('Processing Inward Transfer', [
            'external_transaction_reference' => $external_transaction_reference,
            'account_number' => $account_number,
            'amount' => $amount,
            'currency' => $currency
        ]);
        event(new WalletTransactionReceived($account_number, $amount, $fees, $currency, $external_transaction_reference));
    }

    protected function processSuccessfulOutwardTransfer()
    {
        $external_transaction_reference = $this->payload['data']['paymentReference'] ?? $this->payload['data']['sessionId'];
        $account_number = $this->payload['data']['creditAccountNumber'];
        
        $sender_transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->with(['wallet', 'user', 'feeTransactions'])
            ->first();
        
        if ($sender_transaction) {
            event(new TransferSuccessful(
                $sender_transaction, 
                $account_number, 
                Settings::where('name', 'currency')->first()->value, 
                $this->payload['data']['creditAccountName']
            ));
        }
    }

    protected function processFailedTransfer()
    {
        $external_transaction_reference = $this->payload['data']['paymentReference'] ?? $this->payload['data']['sessionId'];
        $account_number = $this->payload['data']['creditAccountNumber'];
        
        $sender_transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->with(['feeTransactions'])
            ->first();
        
        if ($sender_transaction) {
            event(new TransferFailed(
                $sender_transaction, 
                $this->payload['data']['creditAccountName']
            ));
        }
    }

    protected function processSuccessfulSubscription()
    {
        $external_transaction_reference = $this->payload['data']['paymentReference'] ?? $this->payload['data']['sessionId'];
        
        $transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->with(['wallet', 'user', 'feeTransactions'])
            ->first();
        
        if ($transaction) {
            event(new SubscriptionSuccessfulEvent($transaction));
        }
    }
    
    
    protected function processFailedSubscription()
    {
        $external_transaction_reference = $this->payload['data']['paymentReference'] ?? $this->payload['data']['sessionId'];
        
        $transaction = Transaction::where('external_transaction_reference', $external_transaction_reference)
        ->whereIn('status', ['PENDING', 'PROCESSING'])
        ->with(['feeTransactions', 'user'])
        ->first();
        
        if ($transaction) {
            event(new SubscriptionFailedEvent($transaction));
        }
    }
}