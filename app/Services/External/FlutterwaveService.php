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
use InvalidArgumentException;

/**
 * Class FlutterwaveService
 *
 * Implementation of the PaymentGateway interface for Flutterwave.
 *
 * @package App\Services
 */

class FlutterwaveService
{
    /**
     * The base URL for Flutterwave API.
     *
     * @var string
     */
    private static $baseUrl;
    private static $callbackUrl;

    /**
     * FlutterwaveService constructor.
     *
     * @param string $baseUrl The base URL for Flutterwave API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
        self::$callbackUrl = env('APP_URL') . '/api/v1/webhooks/flutterwave';
    }

    /**
     * Get a list of banks from the Flutterwave API.
     *
     * @return array
     * @throws Exception
     */
    public function getBanks(): array
    {
        try {

            $url = self::$baseUrl . '/banks/NG';

            $response = Http::talkToFlutterwave($url, 'GET');

            $banks = $response['data'] ?? [];

            return array_map(function ($item) {
                return [
                    'name' => $item['name'],
                    'code' => $item['code'],
                ];
            }, $banks);
        } catch (Exception $e) {
            Log::error('Error Encountered at Get Banks method in Flutterwave Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function resolveAccount(string $account_number, string $account_bank): array
    {
        try {

            $url = self::$baseUrl . '/accounts/resolve';

            $data = [
                'account_number' => $account_number,
                'account_bank' => $account_bank,
            ];

            $response = Http::talkToFlutterwave($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Resolve Account method in Flutterwave Service: ' . $e->getMessage());
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

            $response = Http::talkToFlutterwave($url, 'POST', $data);
            
            if ($response['status'] !== 'success') {
                throw new Exception('Error verifying BVN: ' . $response['message']);
            }
            
            $reference = $response['data']['reference'] ?? null; 
            
            if (!$reference) {
                throw new InvalidArgumentException('Error verifying BVN: No reference found in response');
            }
            
            // Send the reference gotten to retrieve the BVN information 
            
            $verification_url = self::$baseUrl . '/bvn/verifications/' . $reference;
            
            $verification_response = Http::talkToFlutterwave($verification_url, 'GET');

            if ($verification_response['status'] !== 'success') {
                throw new Exception('Error verifying BVN: ' . $verification_response['message']);
            }
            
            $response_data = $verification_response['data']['bvn_data'] ?? null;

            if (is_null($response_data)) {
                throw new Exception('Error verifying BVN: No data found in response');
            }
            
            if ($response_data['firstName'] !== $verification_data->user->first_name || $response_data['surname'] !== $verification_data->user->last_name) {
                throw new InvalidArgumentException('Error verifying BVN: Name mismatch');
            }
            
            if ($response_data['nin'] !== $verification_data->nin) {
                throw new InvalidArgumentException('Error verifying BVN: NIN mismatch');
            }
            
            $userService = resolve(UserService::class);
            $userService->updateUserAccount($verification_data->user, [
                'bvn_status' => 'SUCCESSFUL',
                'kyc_status' => 'SUCCESSFUL',
            ]);

            return 'BVN verified successfully';
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify BVN method in Flutterwave Service: ' . $e->getMessage());
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

            $response = Http::talkToFlutterwave($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create PSA method in Flutterwave Service: ' . $e->getMessage());
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

            $response = Http::talkToFlutterwave($url, 'POST', $data);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at transfer method in Flutterwave Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPSA(string $account_reference, string $currency): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts/' . $account_reference . '/balances?currency=' . $currency;

            $response = Http::talkToFlutterwave($url);

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getting PSA method in Flutterwave Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deletePSA(string $account_reference): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts/' . $account_reference;

            $response = Http::talkToFlutterwave($url, 'DELETE');

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at delete PSA method in Flutterwave Service: ' . $e->getMessage());
            throw $e;
        }
    }
}