<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Services\PurchaseAirtime;
use App\Events\User\Services\PurchaseAirtimeUpdate;
use App\Models\Service;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;

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
                status: $provider->status ?? false,
                percentage_charge: $provider->percentage_charge ?? 0.00,
                fixed_charge: $provider->fixed_charge ?? 0.00,
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

        $provider = $this->getAirtimeServiceProvider();  
        $percentage = $provider->percentage_charge ?? 0.00;
        $fixed = $provider->fixed_charge ?? 0.00;
        $charge_type = $fixed > 0 ? 'fixed' : 'percentage';
        $totalAmount = $charge_type === 'fixed' 
                ? $data['amount'] + $fixed 
                : $data['amount'] + ($data['amount'] * ($percentage / 100));
        $fees = $charge_type === 'fixed' 
                ? $fixed 
                : $data['amount'] * ($percentage / 100);
        
        $data['total_amount'] = $totalAmount;
        $data['fees'] = $fees;

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
        $provider = $this->getAirtimeServiceProvider();

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
        ];
        event(new PurchaseAirtime($user->wallet, $data['amount'], $data['fees'], $status, Settings::where('name', 'currency')->first()->value, $response['id'], $response['reference'], $payload));
    }

    private function handleUpdatedPurchase(Transaction $transaction, string $status)
    {
        event(new PurchaseAirtimeUpdate($transaction, $status));
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