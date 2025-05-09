<?php

namespace App\Services\External;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Contracts\PaymentGateway;
use App\Models\Settings;
use App\Services\UserService;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

/**
 * Class PaystackService
 *
 * Implementation of the PaymentGateway interface for Paystack.
 *
 * @package App\Services
 */
class PaystackService implements PaymentGateway
{
    /**
     * The base URL for Paystack API.
     *
     * @var string
     */
    private static $baseUrl;

    /**
     * PaystackService constructor.
     *
     * @param string $baseUrl The base URL for Paystack API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
    }

    /**
     * Get a list of banks from the Paystack API.
     *
     * @return array
     * @throws Exception
     */
    public function getBanks(): array
    {
        try {

            $url = self::$baseUrl . '/bank?country=nigeria';

            $response = Http::talkToPaystack($url, 'GET');

            $banks = $response['data'] ?? [];

            return array_map(function ($item) {
                return [
                    'name' => $item['name'],
                    'code' => $item['code'],
                ];
            }, $banks);
        } catch (Exception $e) {
            Log::error('Error Encountered at Get Banks method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Resolve a bank account using the Paystack API.
     *
     * @param string $accountNumber
     * @param string $bankCode
     * @return array
     * @throws Exception
     */
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $url = self::$baseUrl . '/bank/resolve?account_number=' . $accountNumber . '&' . 'bank_code=' . $bankCode;

            // $response = Http::talkToPaystack($url);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->get($url);

            return $response->json();
        } catch (Exception $e) {
            Log::error('Error Encountered at Resolve Account method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Initialize a transaction using the Paystack API.
     *
     * @param float $amount
     * @param string $email
     * @param string $reference
     * @return array
     * @throws Exception
     */
    public function initializeTransaction(float $amount, string $email, string $reference): array
    {
        try {
            $url = self::$baseUrl . '/transaction/initialize';

            $data = [
                'amount' => $amount,
                'email' => $email,
                'reference' => $reference,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Initialize Transaction method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a transaction using the Paystack API.
     *
     * @param string $reference
     * @return array
     * @throws Exception
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $url = self::$baseUrl . '/transaction/verify/' . $reference;

            $response = Http::talkToPaystack($url);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify Transaction method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a refund using the Paystack API.
     *
     * @param string $reference
     * @return array
     * @throws Exception
     */
    public function createRefund(string $reference): array
    {
        try {
            $url = self::$baseUrl . '/refund';

            $data = [
                'transaction' => $reference,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create Refund method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a transfer recipient using the Paystack API.
     *
     * @param string $accountNumber
     * @param string $bankCode
     * @param string $name
     * @param string $type
     * @param string $currency
     * @return array
     * @throws Exception
     */
    public function createRecipient(string $accountNumber, string $bankCode, string $name, string $type, string $currency): array
    {
        try {
            $url = self::$baseUrl . '/transferrecipient';

            $data = [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'name' => $name,
                'type' => $type,
                'currency' => $currency,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response['data'] ?? [];
        } catch (Exception $e) {
            Log::error('Error Encountered at Create Recipient method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Create a transfer using the Paystack API.
     *
     * @param string $recipientCode
     * @param string $amount
     * @param string $reason
     * @return array
     * @throws Exception
     */
    public function createTransfer(string $recipientCode, string $amount, string $reason, ?string $reference): array
    {
        try {
            // Split $amount using '.'
            $amountParts = explode('.', $amount);

            $url = self::$baseUrl . '/transfer';

            $data = [
                'source' => 'balance',
                'recipient' => $recipientCode,
                'amount' => $amountParts[0],
                'reason' => $reason,
                'reference' => $reference ?? Str::uuid()
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create Transfer method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a customer using the Paystack API.
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $phone
     * @return array
     * @throws Exception
     */
    public function createCustomer(string $firstName, string $lastName, string $email, string $phone): array
    {
        try {
            $url = self::$baseUrl . '/customer';

            $data = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create Customer method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyBVN (object $verification_data)
    {
        try {
            // Ensure user already has a customer code
            if (!$verification_data->user->hasCustomerCode()) {
                throw new InvalidArgumentException('Cannot proceed to validate BVN. Ensure your mobile number is updated.');
            }
            
            $paystackService = resolve(PaystackService::class);
            
            $paystackService->validateCustomer($verification_data->user->customer_code, $verification_data->user->first_name, $verification_data->user->last_name, $verification_data->account_number, $verification_data->bvn, $verification_data->bank_code);
            
            $userService = resolve(UserService::class);
            $user = $userService->updateUserAccount($verification_data->user, [
                'bvn_status' => 'PENDING',
                'kyc_status' => 'PENDING'
            ]);
            
            return 'BVN Verification submitted successfully.';
        } catch (Exception $e) {
            Log::error('VERIFY USER BVN: Error Encountered: ' . $e->getMessage());
        }
    }

    /**
     * Validate customer information using the Paystack API.
     *
     * @param string $customerCode
     * @param string $firstName
     * @param string $lastName
     * @param string $accountNumber
     * @param string $bvn
     * @param string $bankCode
     * @return array
     * @throws Exception
     */
    public function validateCustomer(string $customerCode, string $firstName, string $lastName, string $accountNumber, string $bvn, string $bankCode): array
    {
        try {
            $url = self::$baseUrl . '/customer/' . $customerCode . '/identification';

            $data = [
                'country' => Settings::where('name', 'country')->first()->value,
                'type' => 'bank_account',
                // 'first_name' => $firstName,
                // 'last_name' => $lastName,
                'first_name' => 'Uchenna',
                'last_name' => 'Okoro',
                'account_number' => $accountNumber,
                'bvn' => $bvn,
                'bank_code' => $bankCode,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Validate Customer method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a Dedicated Virtual Account using the Paystack API.
     *
     * @param string $customerCode
     * @return array
     * @throws Exception
     */
    public function createDVA(string $customerCode): array
    {
        try {
            // Get DVA_DEFAULT_BANK from .env
            $dvaDefaultBank = env('DVA_DEFAULT_BANK') == 'WEMA' ? 'wema-bank' : 'titan-paystack';

            // Apply additional condition for 'sk_test'
            $dvaDefaultBank = substr(env('PAYSTACK_SECRET_KEY'), 0, 7) == 'sk_test' ? 'test-bank' : $dvaDefaultBank;

            $url = self::$baseUrl . '/dedicated_account';

            $data = [
                'customer' => $customerCode,
                'preferred_bank' => $dvaDefaultBank,
            ];

            $response = Http::talkToPaystack($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create DVA method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Requery a Dedicated Virtual Account using the Paystack API.
     *
     * @param string $accountNumber
     * @param string $bankName
     * @param string $date
     * @return Response
     * @throws Exception
     */
    public function requeryDVA(string $accountNumber, string $bankName, string $date): Response
    {
        try {
            $url = self::$baseUrl . '/dedicated_account/requery?account_number=' . $accountNumber . '&provider_slug=' . $bankName . '&date=' . $date;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->get($url);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Requery DVA method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteDVA(string $dedicated_account_id): array
    {
        try {
            $url = self::$baseUrl . '/dedicated_account/' . $dedicated_account_id;

            $response = Http::talkToPaystack($url, 'DELETE');

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Delete DVA method in Paystack Service: ' . $e->getMessage());
            throw $e;
        }
    }
}
