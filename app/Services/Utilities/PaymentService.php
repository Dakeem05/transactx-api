<?php

namespace App\Services\Utilities;

use App\Dtos\Utilities\PaymentProviderDto;
use Exception;
use App\Models\Service;
use App\Services\External\FlutterwaveService;
use App\Services\External\PaystackService;

/**
 * Class PaymentService
 *
 * Implementation of the Payment Gateways interface for Payment.
 *
 * @package App\Services
 */

class PaymentService
{
    public $payment_service_provider;
    public $flutterwaveService;
    public $paystackService;

    public function __construct ()
    {
        $payment_service = Service::where('name', 'payments')->first();

        
        if (!$payment_service) {
            throw new Exception('Payment service not found');
        }
        
        if ($payment_service->status === false) {
            throw new Exception('Payment service is currently unavailable');
        }
        $this->payment_service_provider = $payment_service->providers->where('status', true)->first();
        
        if (is_null($this->payment_service_provider)) {
            throw new Exception('Payment service provider not found');
        }

        $this->flutterwaveService = app(FlutterwaveService::class);
        $this->paystackService = app(PaystackService::class);
    }
    /**
     * Get the active payment service provider.
     *
     * @return string
     */
    public function getPaymentServiceProvider()
    {
        if (!$this->payment_service_provider) {
            throw new Exception('Payment service provider not found');
        }
    
        return PaymentProviderDto::from($this->payment_service_provider);
    }

    public function getBanks()
    {
        $provider = $this->getPaymentServiceProvider();
        
        if ($provider->name === 'flutterwave') {
            if (!$this->flutterwaveService) {
                throw new Exception('Flutterwave service not found');
            }

            return $this->flutterwaveService->getBanks();
        }
        if ($provider->name === 'paystack') {
            if (!$this->paystackService) {
                throw new Exception('Paystack service not found');
            }

            return $this->paystackService->getBanks();
        }
    }

    public function resolveAccount(string $account_number, string $bank_code)
    {
        $provider = $this->getPaymentServiceProvider();
        
        if ($provider->name === 'flutterwave') {
            if (!$this->flutterwaveService) {
                throw new Exception('Flutterwave service not found');
            }

            return $this->flutterwaveService->resolveAccount($account_number, $bank_code);
        }
        if ($provider->name === 'paystack') {
            if (!$this->paystackService) {
                throw new Exception('Paystack service not found');
            }

            return $this->paystackService->resolveAccount($account_number, $bank_code);
        }
    }

    public function verifyBVN (object $verification_data)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name === 'flutterwave') {
            if (!$this->flutterwaveService) {
                throw new Exception('Flutterwave service not found');
            }

            return $this->flutterwaveService->verifyBVN($verification_data);
        }
        if ($provider->name === 'paystack') {
            if (!$this->paystackService) {
                throw new Exception('Paystack service not found');
            }

            return $this->paystackService->verifyBVN($verification_data);
        }
    }

    public function transfer (array $transfer_data)
    {
        $provider = $this->getPaymentServiceProvider();

        if ($provider->name === 'flutterwave') {
            if (!$this->flutterwaveService) {
                throw new Exception('Flutterwave service not found');
            }

            return $this->flutterwaveService->transfer($transfer_data);
        }
        if ($provider->name === 'paystack') {
            if (!$this->paystackService) {
                throw new Exception('Paystack service not found');
            }

            // return $this->paystackService->transfer($transfer_data);
        }
    }


}