<?php

namespace App\Services\External;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Contracts\PaymentGateway;
use App\Events\User\Wallet\CreateWalletEvent;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

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

            if ($response['statusCode'] === 400) {
                throw new Exception('Error resolving account: ' . $response['message']);
            }

            return [
                'data' => [
                    'account_name' => $response['data']['accountName'],
                    'account_number' => $response['data']['accountNumber'],
                ]
            ];
        } catch (Exception $e) {
            Log::error('Error Encountered at Resolve Account method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyBVN (object $verification_data)
    {
        try {
            // Initiate the BVN verification consent process
            $url = self::$baseUrl . '/identity/v2';
            
            $data = [
                'type' => 'BVN',
                'number' => $verification_data->bvn,
                'debitAccountNumber' => config('services.safehaven.account_number'),
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);
            
            if (strtolower($response['data']['status']) !== 'success') {
                throw new Exception('Error verifying BVN: Invalid BVN or other verification error.');
            }
            
            return [
                'message' => 'BVN verification has been initiated. Check your phone number for a verification code.',
                'data' => [
                    'verification_id' => $response['data']['_id'],
                ]
            ];
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify BVN method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function validateBVN (object $verification_data)
    {
        try {
            // Initiate the BVN verification consent process
            $url = self::$baseUrl . '/identity/v2/validate';
            
            $data = [
                'type' => 'BVN',
                'identityId' => $verification_data->verification_id,
                'otp' => $verification_data->otp,
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);
            if (strtolower($response['data']['status']) !== 'success') {
                throw new Exception('Error verifying BVN: Invalid BVN or other verification error.');
            }
            $response_data = $response['data']['providerResponse'] ?? null;
            
            if (is_null($response_data)) {
                throw new Exception('Error verifying BVN: No data found in response');
            }
            
            if (strtolower($response_data['firstName']) !== strtolower($verification_data->user->first_name) || strtolower($response_data['lastName']) !== strtolower($verification_data->user->last_name)) {
                throw new InvalidArgumentException('Error verifying BVN: Name mismatch');
            }
            
            $userService = resolve(UserService::class);
            $userService->updateUserAccount($verification_data->user, [
                'bvn_status' => 'SUCCESSFUL',
                'kyc_status' => 'SUCCESSFUL',
                // 'bvn' => $verification_data->bvn,
            ]);
            
            // event(new CreateWalletEvent($verification_data->user, $verification_data->bvn, $verification_data->verification_id, $verification_data->otp));

            return 'BVN verified successfully';
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify BVN method in Safehaven Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a Individual SubAccount using the Paystack API.
     *
     * @param User $user
     * @param string $country
     * @return array
     * @throws Exception
     */
    public function createISA(User $user, string $country, string $bvn, string $verification_id, string $otp): array
    {
        try {

            $url = self::$baseUrl . '/accounts/v2/subaccount';

            $data = [
                'phoneNumber' => $country === "NG" ? '+234' . substr($user->phone_number, 1) : $user->phone_number,
                'emailAddress' => $user->email,
                'externalReference' => str_replace('-', '', $user->wallet->id),
                'identityType' => 'BVN',
                'identityNumber' => $bvn,
                'identityId' => $verification_id,
                'otp' => $otp,
                'autoSweep' => false,
                'autoSweepDetails' => [
                    'schedule' => 'Instant'
                ]
            ];

            $response = Http::talkToSafehaven($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create ISA method in Safehaven Service: ' . $e->getMessage());
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

    public function getISA(string $id, string $currency): array
    {
        try {

            $url = self::$baseUrl . '/accounts/' . $id;
            $response = Http::talkToSafehaven($url);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getting ISA method in Safehaven Service: ' . $e->getMessage());
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