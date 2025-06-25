<?php

namespace App\Console\Commands\User\Services;

use App\Models\Transaction;
use App\Services\Utilities\CableTVService;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class CableTVServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cabletv-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cable TV service command to process pending transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = Transaction::where('type', 'CABLETV')->where('status', 'PENDING')->with(['feeTransactions'])->get();
        if(!$transactions->isEmpty()) {
            foreach ($transactions as $transaction) {
                $cabletvService = resolve(CableTVService::class);
                $cabletvService->pendingPurchase($transaction);
            }
        }
    }
}
