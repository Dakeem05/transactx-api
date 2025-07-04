<?php

namespace App\Listeners\User\Banking;

use App\Events\User\Banking\ProcessBankAccountReauthorized;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBankAccountReauthorizedListener implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(ProcessBankAccountReauthorized $event): void
    {
        $payload = $event->payload;
        $account = $event->account;
        try {
            DB::beginTransaction();
            $account->update([
                'account_id' => $payload['data']['account'] ?? $account->account_id,
            ]);
            $account->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ProcessBankAccountReauthorizedListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
