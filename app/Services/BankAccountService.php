<?php

namespace App\Services;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Models\LinkedBankAccount;
use App\Models\MonoApiCallLog;
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

            $this->createLinkedBankAccountRecord($user, $response['data']);
            
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
    /**
     * Get transactions with rate limiting
     */
    public function fetchTransactions(User $user, string $ref)
    {
        $provider = $this->getBankingServiceProvider();
        
        if ($provider->name == 'mono') {
            DB::beginTransaction();
            $account = $this->fetchLinkedBankAccountRecord($user, $ref);
            
            $callLogs = $this->logAndCheckRateLimit($account, 'transactions');
            
            $monoService = resolve(MonoService::class);
            $response = $monoService->fetchTransactions($account->account_id);
            dd($response);
            $responseJson = $response->json();

            if (!isset($responseJson['status']) || strtolower($responseJson['status']) !== 'successful') {
                DB::rollBack();
                throw new Exception('Failed to fetch transactions: ' . ($responseJson['message'] ?? 'Unknown error'));
            }

            if (!isset($response->headers()['x-has-new-data']) || !isset($response->headers()['x-job-id']) || !isset($response->headers()['x-job-status'])) {
                DB::rollBack();
                throw new Exception('Failed to fetch transactions');
            }
            $callLogs->update([
                'has_new_data' => $response->headers()['x-has-new-data'] === 'true',
                'job_status' => $response->headers()['x-job-status'],
                'job_id' => $response->headers()['x-job-id'],
            ]);
            DB::commit();
        } else {
            throw new \Exception('Unsupported provider');
        }
        
    }

    /**
     * Log API call and check rate limits
     */
    private function logAndCheckRateLimit(LinkedBankAccount $account, string $type = 'transactions'): MonoApiCallLog
    {
        // Get today's call count
        $dailyCalls = MonoApiCallLog::where('linked_bank_account_id', $account->id)
            ->where('type', $type)
            ->wheredate('created_at', now()->format('Y-m-d'))
            ->count();
            
        // Get monthly call count
        $monthlyCalls = MonoApiCallLog::where('linked_bank_account_id', $account->id)
            ->where('type', $type)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
            
        // Check against limits (adjust numbers as needed)
        $dailyLimit = 10;
        $monthlyLimit = 100;
        
        if ($dailyCalls >= $dailyLimit) {
            DB::rollBack();
            throw new InvalidArgumentException("Daily API call limit reached ($dailyCalls/$dailyLimit)");
        }
        
        if ($monthlyCalls >= $monthlyLimit) {
            DB::rollBack();
            throw new InvalidArgumentException("Monthly API call limit reached ($monthlyCalls/$monthlyLimit)");
        }
        
        // Log the call
        return MonoApiCallLog::create([
            'linked_bank_account_id' => $account->id,
            'type' => $type,
        ]);
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
}