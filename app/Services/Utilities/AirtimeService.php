<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Models\Service;
use App\Models\Settings;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;
use Illuminate\Support\Facades\DB;

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
        DB::beginTransaction();

        // try {
            $provider = $this->getAirtimeServiceProvider();
            if ($provider->name == 'safehaven') {
                $payload = $this->createDataPayload($data, $user);
                $data = $this->purchaseService($payload, 'AIRTIME');
                dd($data);
            }
            DB::commit();
        // } catch (\Exception $e) {
        //     throw new Exception($e);
        //     DB::rollBack();
        // }
    }

    private function createDataPayload(array $data, User $user)
    {
        return [
            'serviceCategoryId' => $data['id'],
            'amount' => $data['amount'],
            'channel' => 'Web',
            'debitAccountNumber' => $user->wallet->virtualBankAccount->account_number,
            // 'phoneNumber' => $data['phone_number'],
            'phoneNumber' => Settings::where('name', 'country')->first()->value === "NG" ? '+234' . substr($data['phone_number'], 1) : $data['phone_number'],
        ];
    }
}