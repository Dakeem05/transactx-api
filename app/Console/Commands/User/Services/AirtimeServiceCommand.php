<?php

namespace App\Console\Commands\User\Services;

use App\Models\Transaction;
use App\Services\Utilities\AirtimeService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class AirtimeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:airtime-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Airtime service command to process pending transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'AIRTIME')->where('status', 'PENDING')->with(['feeTransactions'])->get();
        if(!$transactions->isEmpty()) {
            foreach ($transactions as $transaction) {
                $airtimeService = resolve(AirtimeService::class);
                $airtimeService->pendingPurchase($transaction);
            }
        }
    }
}
