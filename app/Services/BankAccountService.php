<?php

namespace App\Services;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Models\LinkedBankAccount;
use App\Models\Service;
use App\Models\User;
use App\Services\External\MonoService;
use Illuminate\Support\Str;
use Exception;
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

    private function getBankingServiceProvider()
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

    public function linkAccount(User $user) 
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

    public function relinkAccount(User $user, $ref) 
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