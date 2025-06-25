<?php

namespace App\Console\Commands\User\Services;

use App\Models\Transaction;
use App\Services\Utilities\UtilityService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class UtilityServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:utility-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Utility service command to process pending transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'UTILITY')->where('status', 'PENDING')->with(['feeTransactions'])->get();
        if(!$transactions->isEmpty()) {
            foreach ($transactions as $transaction) {
                $utilityService = resolve(UtilityService::class);
                $utilityService->pendingPurchase($transaction);
            }
        }
    }
}
