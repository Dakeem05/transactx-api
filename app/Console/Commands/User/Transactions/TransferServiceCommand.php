<?php

namespace App\Console\Commands\User\Transactions;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class TransferServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transfer-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer service command to process pending transfers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'SEND_MONEY')->where('status', 'PENDING')->with(['feeTransactions'])->get();
        if(!$transactions->isEmpty()) {
            foreach ($transactions as $transaction) {
                $transactionService = resolve(TransactionService::class);
                $transactionService->pendingTransfers($transaction);
            }
        }
    }
}
