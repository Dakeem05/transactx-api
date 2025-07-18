<?php

namespace App\Services;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Models\LinkedBankAccount;
use App\Models\LinkedBankAccountApiCallLog;
use App\Models\Service;
use App\Models\User;
use App\Services\External\MonoService;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankAccountService
{
    public $banking_service_provider;
    
    public function __construct ()
    {
        $banking_service = Service::where('name', 'banking')->first();

        if (!$banking_service) {
            throw new Exception('Banking service not found');
        }
        
        if ($banking_service->status === false) {
            throw new Exception('Banking service is currently unavailable');
        }
        $this->banking_service_provider = $banking_service->providers->where('status', true)->first();
        
        if (is_null($this->banking_service_provider)) {
            throw new Exception('Banking service provider not found');
        }
    }

    /**
     * Get the banking service provider details.
     *
     * @return ServiceProviderDto
     * @throws Exception
     */
    private function getBankingServiceProvider(): ServiceProviderDto
    {
        if (!$this->banking_service_provider) {
            throw new Exception('Banking service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->banking_service_provider);

        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false,
                percentage_charge: $provider->percentage_charge ?? 0.00,
                fixed_charge: $provider->fixed_charge ?? 0.00,
            );
        }
        return $provider;
    }

    /**
     * Link a bank account for a user.
     *
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function linkAccount(User $user): array
    {
        $provider = $this->getBankingServiceProvider();

        if ($provider->name === 'mono') {
            $reference = Str::uuid();

            $monoService = resolve(MonoService::class);
            $response = $monoService->linkAccount($user, $reference);

            if (!isset($response['status']) && strtolower($response['status']) !== 'successful') {
                throw new Exception('Failed to link bank account: ' . ($response['message'] ?? 'Unknown error'));
            }

            $this->createLinkedBankAccountRecord($user, $response['data']);
            
            return [
                'url' => $response['data']['mono_url']
            ];
        } else {
            throw new Exception('Unsupported banking service provider: ' . $provider->name);
        }
    }

    /**
     * Relink an existing bank account for a user.
     *
     * @param User $user
     * @param string $ref
     * @return array
     */
    public function relinkAccount(User $user, string $ref): array
    {
        $provider = $this->getBankingServiceProvider();

        if ($provider->name === 'mono') {
            $account = $this->fetchLinkedBankAccountRecord($user, $ref);

            if (!$account) {
                throw new InvalidArgumentException('Linked bank account not found');
            }

            $monoService = resolve(MonoService::class);
            $response = $monoService->relinkAccount($account);

            if (!isset($response['status']) && strtolower($response['status']) !== 'successful') {
                throw new Exception('Failed to relink bank account: ' . ($response['message'] ?? 'Unknown error'));
            }
            
            return [
                'url' => $response['data']['mono_url']
            ];
        } else {
            throw new Exception('Unsupported banking service provider: ' . $provider->name);
        }
    }

    /**
     * List all linked bank accounts for a user.
     *
     * @param User $user
     */
    public function listAccounts(User $user)
    {
        if (!$user->linkedBankAccounts) {
            return [];
        }

        return $user->linkedBankAccounts;
    }
   

    public function fetchTransactions(User $user, string $ref, $request)
    {
        $provider = $this->getBankingServiceProvider();
        
        if ($provider->name == 'mono') {
            DB::beginTransaction();
            $account = $this->fetchLinkedBankAccountRecord($user, $ref);

            
            $number_of_months = 1;
            if (isset($request->number_of_months) && is_numeric($request->number_of_months) && $request->number_of_months > 0) {
                $number_of_months = (int)$request->number_of_months;
            }
            $endDate = now()->format('d-m-Y');
            $startDate = now()->subMonths($number_of_months)->format('d-m-Y');
            
            $realtime = $this->logAndCheckRateLimit($user, $account, $provider->name, 'transactions');
            
            $monoService = resolve(MonoService::class);
            $response = $monoService->fetchTransactions($account->account_id, $realtime, $startDate, $endDate);
            
            if (!isset($response['status']) || strtolower($response['status']) !== 'successful') {
                DB::rollBack();
                throw new Exception('Failed to fetch transactions: ' . ($response['message'] ?? 'Unknown error'));
            }
            DB::commit();

            $data = [];
            foreach ($response['data'] as $transaction) {
                $data[] = [
                    'id' => $transaction['id'],
                    'amount' => $transaction['amount'] / 100,
                    'narration' => $transaction['narration'],
                    'type' => $transaction['type'],
                    'category' => $transaction['category'],
                    'date' => $transaction['date'],
                    'currency' => $transaction['currency'],
                ];
            }

            return $data;
        } else {
            throw new \Exception('Unsupported provider');
        }
        
    }

    private function logAndCheckRateLimit(User $user, LinkedBankAccount $account, string $provider, string $type = 'transactions'): bool
    {
        $features = json_decode($user->subscription->model->features);

        $dailyCalls = LinkedBankAccountApiCallLog::where('user_id', $user->id)
            ->where('provider', $provider)
            ->wheredate('created_at', now()->format('Y-m-d'))
            ->count();

        $monthlyCalls = LinkedBankAccountApiCallLog::where('user_id', $user->id)
            ->where('type', $type)
            ->where('provider', $provider)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $latestCall = LinkedBankAccountApiCallLog::where('user_id', $user->id)
            ->where('type', $type)
            ->where('provider', $provider)
            ->latest()
            ->first();
        
        if ($dailyCalls >= $features->auto_bank_transaction_sync->daily_limit) {
            $this->manualBankTransactionSync($user, $account, $features->manual_bank_transaction_sync->amount);
            return true;
        }
        
        if ($monthlyCalls >= $features->auto_bank_transaction_sync->monthly_limit) {
            $this->manualBankTransactionSync($user, $account, $features->manual_bank_transaction_sync->amount);
            return true;
        }
        
        if (!is_null($latestCall)) {
            if (now()->subMinutes($features->auto_bank_transaction_sync->duration) <= $latestCall->created_at) {
                $this->manualBankTransactionSync($user, $account, $features->manual_bank_transaction_sync->amount);
                return true;
            }
        }
        
        $this->createLinkedBankAccountApiCallLog($user, $account);
        return true;
    }

    /**
     * Fetch a linked bank account record by user and reference.
     *
     * @param User $user
     * @param string $ref
     * @return LinkedBankAccount
     */
    private function fetchLinkedBankAccountRecord(User $user, string $ref): LinkedBankAccount
    {
        return LinkedBankAccount::where('user_id', $user->id)
            ->where('reference', $ref)
            ->firstOrFail();
    }

    /**
     * Create a new linked bank account record.
     *
     * @param User $user
     * @param array $data
     * @return LinkedBankAccount
     */
    private function createLinkedBankAccountRecord(User $user, array $data): LinkedBankAccount
    {
        return LinkedBankAccount::create([
            'user_id' => $user->id,
            'customer' => $data['customer'] ?? null,
            'reference' => $data['meta']['ref'] ?? null,
            'provider' => $this->getBankingServiceProvider()->name,
            'balance' => 0
        ]);
    }

    private function createLinkedBankAccountApiCallLog(User $user, LinkedBankAccount $account, string $type = "transactions"): LinkedBankAccountApiCallLog
    {
        return LinkedBankAccountApiCallLog::create([
            'user_id' => $user->id,
            'linked_bank_account_id' => $account->id,
            'type' => $type,
            'provider' => $this->getBankingServiceProvider()->name,
        ]);
    }

    private function manualBankTransactionSync(User $user, LinkedBankAccount $account, int $amount) {
        $narration = "Manual bank transaction sync";
        $payload = [
            'bank_code' => $account->bank_code,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
        ];

        $transactionService = resolve(TransactionService::class);
        $transactionService->bankTransactionRequest($user, $user->wallet->virtualBankAccount, $amount, $narration, $payload);
    }
}