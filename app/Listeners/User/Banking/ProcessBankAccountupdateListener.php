<?php

namespace App\Listeners\User\Banking;

use App\Events\User\Banking\ProcessBankAccountupdate;
use App\Notifications\User\Banking\BankAccountLinkedNotification;
use Brick\Money\Money;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBankAccountupdateListener implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(ProcessBankAccountupdate $event): void
    {
        $payload = $event->payload;
        $account = $event->account;
        $user = $account->user;
        $wallet = $user->wallet;
        DB::beginTransaction();
        try {
            if ($account->account_id == null) {
                $account->update([
                    'account_id' => $payload['data']['account']['_id'] ?? $account->account_id,
                    'account_number' => $payload['data']['account']['accountNumber'] ?? $account->account_number,
                    'account_name' => $payload['data']['account']['name'] ?? $account->account_name,
                    'balance' => Money::of(($payload['data']['account']['balance']) / 100, $wallet->currency) ?? $account->balance,
                    'bank_name' => $payload['data']['account']['institution']['name'] ?? $account->bank_name,
                    'bank_code' => $payload['data']['account']['institution']['bankCode'] ?? $account->bank_code,
                    'type' => $payload['data']['account']['institution']['type'] ?? $account->type,
                    'currency' => $payload['data']['account']['currency'] ?? $account->currency,
                    'data_status' => $payload['data']['meta']['data_status'] ?? $account->data_status,
                    'auth_method' => $payload['data']['meta']['auth_method'] ?? $account->auth_method,
                ]);
                $account->save();
                $user->notify(new BankAccountLinkedNotification($account));
                DB::commit();
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("ProcessBankAccountUpdateListener.handle() - Error Encountered - " . $e->getMessage());
        }
    }
}
