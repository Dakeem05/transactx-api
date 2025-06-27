<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Services\PurchaseData;
use App\Events\User\Services\PurchaseDataUpdate;
use App\Models\Service;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;
use InvalidArgumentException;

class DataService
{
    use SafehavenRequestTrait;

    public $data_service_provider;

    public function __construct ()
    {
        $data_service = Service::where('name', 'data')->first();

        if (!$data_service) {
            throw new Exception('Data service not found');
        }
        
        if ($data_service->status === false) {
            throw new Exception('Data service is currently unavailable');
        }
        $this->data_service_provider = $data_service->providers->where('status', true)->first();
        
        if (is_null($this->data_service_provider)) {
            throw new Exception('Data service provider not found');
        }
    }

    private function getDataServiceProvider()
    {
        if (!$this->data_service_provider) {
            throw new Exception('Data service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->data_service_provider);

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

    public function getNetworks()
    {
        $provider = $this->getDataServiceProvider();

        if ($provider->name == 'safehaven') {
            $biller = $this->getSpecificServiceBiller('DATA');
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

    public function getPlans(array $data)
    {
        $provider = $this->getDataServiceProvider();
        $percentage = $provider->percentage_charge ?? 0.00;
        $fixed = $provider->fixed_charge ?? 0.00;
        $charge_type = $fixed > 0 ? 'fixed' : 'percentage';
        
        if ($provider->name == 'safehaven') {
            $plans = $this->getBillerCategoryProduct($data['id']);
            return array_map(function ($plan) use ($charge_type, $fixed, $percentage) { // Add use here
                $totalAmount = $charge_type === 'fixed' 
                    ? $plan['amount'] + $fixed 
                    : $plan['amount'] + ($plan['amount'] * ($percentage / 100));
                
                return [
                    'validity' => $plan['validity'],
                    'name' => $plan['bundleCode'],
                    'plan' => $plan['bundleCode'],
                    'amount' => $plan['amount'],
                    'total_amount' => $totalAmount,
                ];
            }, $plans);
        }
    }

    public function buyData(array $data, User $user)
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
            $beneficiaryService->addAirtimeAndDataBeneficiary($user->id, 'data', $payload);
        }

        return $this->handleDataPurchase($data, $user);
    }

    public function buyDataToBeneficiary(array $data, User $user)
    {
        $beneficiary = resolve(BeneficiaryService::class)->getBeneficiary($user->id, $data['beneficiary_id']);
        $data['phone_number'] = $beneficiary->payload['phone_number'] ?? null;
        $data['network'] = $beneficiary->payload['network'] ?? null;
        $data['id'] = $beneficiary->payload['id'] ?? null;

        if (!$data['phone_number'] || !$data['network'] || !$data['id']) {
            throw new InvalidArgumentException('Beneficiary details are incomplete.');
        }
        
        $transactionService = resolve(TransactionService::class);
        $transactionService->verifyTransaction($data, $user);

        return $this->handleDataPurchase($data, $user);
    }

    public function handleDataPurchase(array $data, User $user)
    {
        try {
            $provider = $this->getDataServiceProvider();
            if ($provider->name == 'safehaven') {
                $payload = $this->createDataPayload($data, $user);
                $response = $this->purchaseService($payload, 'DATA');
                if (strtolower($response['status']) == "successful")  {
                    $this->handlePurchase($data, $user, $response, 'successful');
                } else {
                    $this->handlePurchase($data, $user, $response, 'processing');
                }
            }
        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    public function pendingPurchase(Transaction $transaction)
    {
        $provider = $this->getDataServiceProvider();

        if ($provider->name == 'safehaven') {
            $response = $this->getPurchaseTransaction($transaction->external_transaction_reference);
            if (strtolower($response['status']) == "processing") {
                $this->handleUpdatedPurchase($transaction, 'processing');
            } else if (strtolower($response['status']) == "successful")  {
                $this->handleUpdatedPurchase($transaction, 'successful');
            } else {
                $this->handleUpdatedPurchase($transaction, 'failed');
            }
        }
    }

    private function handlePurchase(array $data, User $user, array $response, string $status)
    {
        $payload = [
            'phone_number' => $data['phone_number'],
            'network' => $data['network'],
            'validity' => $data['validity'],
            'plan' => $data['plan'],
        ];
        event(new PurchaseData($user->wallet, $data['total_amount'], $status, Settings::where('name', 'currency')->first()->value, $response['id'], $response['reference'], $payload));
    }

    private function handleUpdatedPurchase(Transaction $transaction, string $status)
    {
        event(new PurchaseDataUpdate($transaction, $status));
    }


    private function createDataPayload(array $data, User $user)
    {
        return [
            'amount' => (float)$data['amount'],
            'channel' => "WEB",
            'serviceCategoryId' => $data['id'],
            'bundleCode' => $data['plan'],
            'debitAccountNumber' => $user->wallet->virtualBankAccount->account_number,
            'phoneNumber' => Settings::where('name', 'country')->first()->value === "NG" ? '+234' . substr($data['phone_number'], 1) : $data['phone_number'],
        ];
    }
}