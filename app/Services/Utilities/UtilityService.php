<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Events\User\Services\PurchaseUtility;
use App\Events\User\Services\PurchaseUtilityUpdate;
use App\Models\Service;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryService;
use App\Services\TransactionService;
use App\Traits\SafehavenRequestTrait;
use Exception;
use InvalidArgumentException;

class UtilityService
{
    use SafehavenRequestTrait;

    public $utility_service_provider;

    public function __construct ()
    {
        $utility_service = Service::where('name', 'electricity')->first();

        if (!$utility_service) {
            throw new Exception('Utility service not found');
        }
        
        if ($utility_service->status === false) {
            throw new Exception('Utility service is currently unavailable');
        }
        $this->utility_service_provider = $utility_service->providers->where('status', true)->first();
        
        if (is_null($this->utility_service_provider)) {
            throw new Exception('Utility service provider not found');
        }
    }

    private function getUtilityServiceProvider()
    {
        if (!$this->utility_service_provider) {
            throw new Exception('Utility service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->utility_service_provider);

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
        $provider = $this->getUtilityServiceProvider();

        if ($provider->name == 'safehaven') {
            $biller = $this->getSpecificServiceBiller('UTILITY');
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

    public function verifyNumber(array $data)
    {
        $provider = $this->getUtilityServiceProvider();

        if ($provider->name == 'safehaven') {
            $response = $this->verifyBillerCategoryNumber($data['company_id'], $data['number']);
            return [
                'company_id' => $data['company_id'],
                'name' => $response['name'],
                'meterNo' => $response['meterNo'],
                'vendType' => $response['vendType'],
                'minVendAmount' => $response['minVendAmount'],
                'maxVendAmount' => $response['maxVendAmount'],
                'outstanding' => $response['outstanding'],
                'debtRepayment' => $response['debtRepayment'],
            ];
        }
    }

    public function buySubscription(array $data, User $user)
    {
        $transactionService = resolve(TransactionService::class);

        if ($data['amount'] < $data['min_vend_amount'] || $data['amount'] > $data['max_vend_amount']) {
            throw new InvalidArgumentException('Amount must be between ' . $data['min_vend_amount'] . ' and ' . $data['max_vend_amount']);
        }

        $provider = $this->getUtilityServiceProvider();  
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
                'number' => $data['number'],
                'id' => $data['id'],
                'company' => $data['company'],
                'vendType' => $data['vend_type'],
                'minVendAmount' => $data['min_vend_amount'],
                'maxVendAmount' => $data['max_vend_amount'],
            ];
            $beneficiaryService->addCableTVAndUtiltyBeneficiary($user->id, 'utility', $payload);
        }

        return $this->handleSubscriptionPurchase($data, $user);
    }

    public function handleSubscriptionPurchase(array $data, User $user)
    {
        try {
            $provider = $this->getUtilityServiceProvider();  

            if ($provider->name == 'safehaven') {
                $payload = $this->createDataPayload($data, $user);
                $response = $this->purchaseService($payload, 'UTILITY');
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
        $provider = $this->getUtilityServiceProvider();

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
            'vendType' => $data['vend_type'],
        ];
        event(new PurchaseUtility($user->wallet, $data['amount'], $data['fees'], $status, Settings::where('name', 'currency')->first()->value, $response['id'], $response['reference'], $payload));
    }

    private function handleUpdatedPurchase(Transaction $transaction, string $status)
    {
        event(new PurchaseUtilityUpdate($transaction, $status));
    }


    private function createDataPayload(array $data, User $user)
    {
        return [
            'amount' => (float)$data['amount'],
            'channel' => "WEB",
            'serviceCategoryId' => $data['id'],
            'vendType' => $data['vend_type'],
            'debitAccountNumber' => $user->wallet->virtualBankAccount->account_number,
            'meterNumber' => $data['number'],
        ];
    }
}