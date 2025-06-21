<?php

namespace App\Console\Commands\User\Services;

use App\Models\Transaction;
use App\Services\Utilities\DataService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class DataServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:data-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Data service command to process pending transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'DATA')->where('status', 'PENDING')->with(['feeTransactions'])->get();
        if(!$transactions->isEmpty()) {
            foreach ($transactions as $transaction) {
                $dataService = resolve(DataService::class);
                $dataService->pendingPurchase($transaction);
            }
        }
    }
}
