<?php

namespace App\Listeners\User\Banking;

use App\Events\User\Banking\ProcessBankAccountConnected;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBankAccountConnectedListener implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(ProcessBankAccountConnected $event): void
    {
        $payload = $event->payload;
        $account = $event->account;
        DB::beginTransaction();
        try {
            $account->update([
                'account_id' => $payload['data']['id'] ?? $account->account_id,
                'customer' => $payload['data']['customer'] ?? $account->customer,
            ]);
            $account->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ProcessBankAccountConnectedListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
