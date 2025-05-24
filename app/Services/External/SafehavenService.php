<?php

namespace App\Services\External;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Client\Response;

/**
 * Class SafehavenService
 *
 * Implementation of the PaymentGateway interface for Safehaven.
 *
 * @package App\Services
 */

class SafehavenService
{
    /**
     * The base URL for Safehaven API.
     *
     * @var string
     */
    private static $baseUrl;
    private static $callbackUrl;

    /**
     * SafehavenService constructor.
     *
     * @param string $baseUrl The base URL for Safehaven API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
        self::$callbackUrl = env('APP_URL') . '/api/v1/webhooks/safehaven';
    }

    /**
     * Get a list of banks from the Safehaven API.
     *
     * @return array
     * @throws Exception
     */
    public function getBanks(): array
    {
        try {

            $url = self::$baseUrl . '/transfers/banks';

            $response = Http::talkToSafehaven($url, 'GET');

            $banks = $response['data'] ?? [];

            return array_map(function ($item) {
                return [
                    'name' => $item['name'],
                    'code' => $item['bankCode'],
                ];
            }, $banks);
        } catch (Exception $e) {
            Log::error('Error Encountered at Get Banks method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function resolveAccount(string $account_number, string $account_bank): array
    {
        try {

            $url = self::$baseUrl . '/transfers/name-enquiry';

            $data = [
                'bankCode' => $account_bank,
                'accountNumber' => $account_number,
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);
            dd($response);
            return array_map(function ($item) {
                return [
                    'account_name' => $item['accountName'],
                    'account_number' => $item['accountNumber'],
                ];
            }, $response['data']);
        } catch (Exception $e) {
            Log::error('Error Encountered at Resolve Account method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyBVN (object $verification_data)
    {
        try {
            // Initiate the BVN verification consent process
            $url = self::$baseUrl . '/bvn/verifications';
            
            $data = [
                'bvn' => $verification_data->bvn,
                'firstname' => $verification_data->user->first_name,
                'lastname' => $verification_data->user->last_name,
                'callback_url' => self::$callbackUrl,
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);
            
            if ($response['status'] !== 'success') {
                throw new Exception('Error verifying BVN: ' . $response['message']);
            }
            
            $reference = $response['data']['reference'] ?? null; 
            
            if (!$reference) {
                throw new Exception('Error verifying BVN: No reference found in response');
            }
            
            // Send the reference gotten to retrieve the BVN information 
            
            $verification_url = self::$baseUrl . '/bvn/verifications/' . $reference;
            
            $verification_response = Http::talkToSafehaven($verification_url, 'GET');

            if ($verification_response['status'] !== 'success') {
                throw new Exception('Error verifying BVN: ' . $verification_response['message']);
            }
            
            $response_data = $verification_response['data']['bvn_data'] ?? null;

            if (is_null($response_data)) {
                throw new Exception('Error verifying BVN: No data found in response');
            }
            
            if ($response_data['firstName'] !== $verification_data->user->first_name || $response_data['surname'] !== $verification_data->user->last_name) {
                throw new Exception('Error verifying BVN: Name mismatch');
            }
            
            if ($response_data['nin'] !== $verification_data->nin) {
                throw new Exception('Error verifying BVN: NIN mismatch');
            }
            
            $userService = resolve(UserService::class);
            $userService->updateUserAccount($verification_data->user, [
                'bvn_status' => 'SUCCESSFUL',
                'kyc_status' => 'SUCCESSFUL',
            ]);

            return 'BVN verified successfully';
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify BVN method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a Payout SubAccount using the Paystack API.
     *
     * @param User $user
     * @param string $country
     * @return array
     * @throws Exception
     */
    public function createPSA(User $user, string $country): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts';

            $data = [
                'account_name' => $user->name,
                'email' => $user->email,
                'mobilenumber' => $user->phone_number,
                'country' => $country,
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create PSA method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function transfer(array $data): array
    {
        try {

            $url = self::$baseUrl . '/transfers';

            $data = [
                'account_bank' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'debit_subaccount' => $data['account_reference'],
                'reference' => $data['reference'],
                'debit_currency' => $data['currency'],
                'narration' => $data['narration'],
                'callback_url' => self::$callbackUrl
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at transfer method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPSA(string $account_reference, string $currency): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts/' . $account_reference . '/balances?currency=' . $currency;

            $response = Http::talkToSafehaven($url);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getting PSA method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deletePSA(string $account_reference): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts/' . $account_reference;

            $response = Http::talkToSafehaven($url, 'DELETE');

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at delete PSA method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }
}