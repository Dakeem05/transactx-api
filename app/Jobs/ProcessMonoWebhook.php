<?php

namespace App\Jobs;

use App\Enums\PartnersEnum;
use App\Events\User\Banking\ProcessBankAccountUpdate;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\WalletTransactionReceived;
use App\Jobs\Webhook\ProcessSuccessfulOutwardTransfer;
use App\Models\LinkedBankAccount;
use App\Models\Settings;
use App\Models\Transaction;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonoWebhook implements ShouldQueue
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
            Log::info('Processing Mono webhook in queue', ['payload' => $this->payload]);
            
            $responseData = ['message' => 'Webhook processed'];
            $webhookService->recordIncomingWebhook(
                PartnersEnum::MONO->value,
                $this->payload,
                $responseData,
                200,
                $this->ipAddress
            );
            
            $event_type = $this->payload['event'] ?? null;
            Log::info('Mono webhook event', ['event' => $event_type]);

            if ($event_type === 'mono.events.account_updated') {
                $this->processAccountUpdate();
                return;
            }

            // // Successful transfers webhook
            // if (in_array($event_type, ['transfer']) && $this->payload['data']['type'] === 'Outwards' && in_array($this->payload['data']['status'], ['Created', 'Completed']) && !$this->payload['data']['isReversed']) {
            //     $this->processSuccessfulOutwardTransfer();
            //     return;
            // }
            
            // // Failed transfers webhook
            // if (in_array($event_type, ['transfer']) && $this->payload['data']['type'] === 'Outwards' && $this->payload['data']['isReversed']) {
            //     $this->processFailedTransfer();
            //     return;
            // }

        } catch (\Exception $e) {
            Log::error('Mono Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);
            throw $e;
        }
    }

    protected function processAccountUpdate()
    {
        $account = LinkedBankAccount::where('reference', $this->payload['data']['meta']['ref'])->first();

        if ($account) {
            Log::info('Processing Account update', ['payload', $this->payload]);            
            event(new ProcessBankAccountUpdate($this->payload, $account));
        }
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
}