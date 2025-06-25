<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Services\PurchaseCableTV;
use App\Events\User\Services\PurchaseCableTVUpdate;
use App\Models\Service;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;

class CableTVService
{
    use SafehavenRequestTrait;

    public $cabletv_service_provider;

    public function __construct ()
    {
        $cabletv_service = Service::where('name', 'cabletv')->first();

        if (!$cabletv_service) {
            throw new Exception('Cable TV service not found');
        }
        
        if ($cabletv_service->status === false) {
            throw new Exception('Cable TV service is currently unavailable');
        }
        $this->cabletv_service_provider = $cabletv_service->providers->where('status', true)->first();
        
        if (is_null($this->cabletv_service_provider)) {
            throw new Exception('Cable TV service provider not found');
        }
    }

    private function getCableTVServiceProvider()
    {
        if (!$this->cabletv_service_provider) {
            throw new Exception('Cable TV service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->cabletv_service_provider);

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

    public function getCompanies()
    {
        $provider = $this->getCableTVServiceProvider();

        if ($provider->name == 'safehaven') {
            $biller = $this->getSpecificServiceBiller('CABLE-TV');
            $companies = $this->getBillerCategory($biller['_id']);
            return array_map(function ($company) {
                return [
                    'name' => $company['name'],
                    'code' => $company['identifier'],
                    'id' => $company['_id'],
                    'avatar' => $company['logoUrl'],
                ];
            }, $companies);
        }
    }

    public function getPackages(array $data)
    {
        $provider = $this->getCableTVServiceProvider();
        $percentage = $provider->percentage_charge ?? 0.00;
        $fixed = $provider->fixed_charge ?? 0.00;
        $charge_type = $fixed > 0 ? 'fixed' : 'percentage';
        
        if ($provider->name == 'safehaven') {
            $packages = $this->getBillerCategoryProduct($data['id']);
            return array_map(function ($package) use ($charge_type, $fixed, $percentage) { // Add use here
                $totalAmount = $charge_type === 'fixed' 
                    ? $package['amount'] + $fixed 
                    : $package['amount'] + ($package['amount'] * ($percentage / 100));
                
                return [
                    'name' => $package['name'],
                    'package' => $package['bundleCode'],
                    'amount' => $package['amount'],
                    'total_amount' => $totalAmount,
                ];
            }, $packages);
        }
    }

    public function verifyNumber(array $data)
    {
        $provider = $this->getCableTVServiceProvider();

        if ($provider->name == 'safehaven') {
            $response = $this->verifyBillerCategoryNumber($data['company_id'], $data['number']);
            return [
                'company_id' => $data['company_id'],
                'name' => $response['name'],
                'number' => $response['customernumber'],
            ];
        }
    }

    public function buySubscription(array $data, User $user)
    {
        $transactionService = resolve(TransactionService::class);
        $transactionService->verifyTransaction($data, $user);

        if ($data['add_beneficiary']) {
            $beneficiaryService = resolve(BeneficiaryService::class);
            $payload = [
                'number' => $data['number'],
                'id' => $data['id'],
                'company' => $data['company'],
            ];
            $beneficiaryService->addCableTVAndUtiltyBeneficiary($user->id, 'cabletv', $payload);
        }

        return $this->handleSubscriptionPurchase($data, $user);
    }

    public function handleSubscriptionPurchase(array $data, User $user)
    {
        try {
            $provider = $this->getCableTVServiceProvider();
            if ($provider->name == 'safehaven') {
                $payload = $this->createDataPayload($data, $user);
                $response = $this->purchaseService($payload, 'CABLE-TV');
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
        $provider = $this->getCableTVServiceProvider();

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
            'company' => $data['company'],
            'number' => $data['number'],
            'name' => $data['name'],
            'package' => $data['package'],
        ];
        event(new PurchaseCableTV($user->wallet, $data['total_amount'], $status, Settings::where('name', 'currency')->first()->value, $response['id'], $response['reference'], $payload));
    }

    private function handleUpdatedPurchase(Transaction $transaction, string $status)
    {
        event(new PurchaseCableTVUpdate($transaction, $status));
    }


    private function createDataPayload(array $data, User $user)
    {
        return [
            'amount' => (float)$data['amount'],
            'channel' => "WEB",
            'serviceCategoryId' => $data['id'],
            'bundleCode' => $data['plan'],
            'debitAccountNumber' => $user->wallet->virtualBankAccount->account_number,
            'cardNumber' => $data['number'],
        ];
    }
}