<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Services\PurchaseAirtime;
use App\Models\Service;
use App\Models\Settings;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isNull;

class AirtimeService
{
    use SafehavenRequestTrait;

    public $airtime_service_provider;

    public function __construct ()
    {
        $airtime_service = Service::where('name', 'airtime')->first();

        if (!$airtime_service) {
            throw new Exception('Airtime service not found');
        }
        
        if ($airtime_service->status === false) {
            throw new Exception('Airtime service is currently unavailable');
        }
        $this->airtime_service_provider = $airtime_service->providers->where('status', true)->first();
        
        if (is_null($this->airtime_service_provider)) {
            throw new Exception('Airtime service provider not found');
        }
    }

    private function getAirtimeServiceProvider()
    {
        if (!$this->airtime_service_provider) {
            throw new Exception('Airtime service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->airtime_service_provider);

        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false
            );
        }
        return $provider;
    }

    public function getNetworks()
    {
        $provider = $this->getAirtimeServiceProvider();

        if ($provider->name == 'safehaven') {
            $biller = $this->getSpecificServiceBiller('AIRTIME');
            $networks = $this->getBillerCategory($biller['_id']);
    
            return array_map(function ($network) {
                return [
                    'name' => $network['name'],
                    'code' => $network['identifier'],
                    'id' => $network['_id'],
                    'avatar' => $network['logoUrl'],
                ];
            }, $networks);
        }
    }

    public function buyAirtime(array $data, User $user)
    {
        $transactionService = resolve(TransactionService::class);
        $transactionService->verifyTransaction($data, $user);

        if ($data['add_beneficiary']) {
            $beneficiaryService = resolve(BeneficiaryService::class);
            $payload = [
                'phone_number' => $data['phone_number'],
                'id' => $data['id'],
                'network' => $data['network'],
            ];
            $beneficiaryService->addAirtimeAndDataBeneficiary($user->id, 'airtime', $payload);
        }

        return $this->handleAirtimePurchase($data, $user);
    }

    public function handleAirtimePurchase(array $data, User $user)
    {
        try {
            $provider = $this->getAirtimeServiceProvider();
            if ($provider->name == 'safehaven') {
                $payload = $this->createDataPayload($data, $user);
                $response = $this->purchaseService($payload, 'AIRTIME');
                if (isNull($response)) {
                    return;
                }

                if (strtolower($response['status']) == "processing") {
                    $this->handleProcessingPurchase($data, $user, $response);
                } else if (strtolower($response['status']) == "successful")  {
                    $this->handleSuccessfulPurchase($data, $user, $response);
                }
            }
        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    private function handleProcessingPurchase(array $data, User $user, array $response)
    {
        $payload = [
            'phone_number' => $data['phone_number'],
            'network' => $data['network'],
        ];
        event(new PurchaseAirtime($user->wallet, $data['amount'], 'processing', Settings::where('name', 'currency')->first()->value, $response['reference'], $response['id'], $payload));
    }
    
    private function handleSuccessfulPurchase(array $data, User $user, array $response)
    {
        $payload = [
            'phone_number' => $data['phone_number'],
            'network' => $data['network'],
        ];
        Log::info('handleSuccessfulPurchase payload', $payload);
        event(new PurchaseAirtime($user->wallet, $data['amount'], 'successful', Settings::where('name', 'currency')->first()->value, $response['reference'], $response['id'], $payload));
    }

    private function createDataPayload(array $data, User $user)
    {
        return [
            'amount' => (int)$data['amount'],
            'channel' => "WEB",
            'serviceCategoryId' => $data['id'],
            'debitAccountNumber' => $user->wallet->virtualBankAccount->account_number,
            'phoneNumber' => Settings::where('name', 'country')->first()->value === "NG" ? '+234' . substr($data['phone_number'], 1) : $data['phone_number'],
        ];
    }
}