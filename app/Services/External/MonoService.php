<?php

namespace App\Services\External;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Class MonoService
 *
 * Implementation of the PaymentGateway interface for Mono.
 *
 * @package App\Services
 */

class MonoService
{
    /**
     * The base URL for Mono API.
     *
     * @var string
     */
    private static $baseUrl;
    private static $callbackUrl;

    /**
     * MonoService constructor.
     *
     * @param string $baseUrl The base URL for Mono API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
        self::$callbackUrl = env('APP_URL') . '/api/v1/webhooks/mono';
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

            $response = Http::talkToMono($url, 'POST', $data);
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
                'bvn' => Crypt::encryptString($verification_data->bvn),
            ]);
            
            return 'BVN verified successfully';
        } catch (Exception $e) {
            Log::error('Error Encountered at Verify BVN method in Mono Service: ' . $e->getMessage());
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
                'externalReference' => $user->wallet->id,
                'identityType' => 'BVN',
                'identityNumber' => $bvn,
                'identityId' => $verification_id,
                'otp' => $otp,
                'autoSweep' => false,
                'autoSweepDetails' => [
                    'schedule' => 'Instant'
                ]
            ];

            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at Create ISA method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function transfer(array $data): array
    {
        try {

            $url = self::$baseUrl . '/transfers';

            $data = [
                'nameEnquiryReference' => $data['session_id'],
                'debitAccountNumber' => $data['debit_account_number'],
                'beneficiaryBankCode' => $data['bank_code'],
                'beneficiaryAccountNumber' => $data['account_number'],
                'amount' => $data['amount'],
                'saveBeneficiary' => false,
                'narration' => $data['narration'],
                'paymentReference' => $data['reference'],
            ];
            
            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at transfer method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getISA(string $id, string $currency): array
    {
        try {

            $url = self::$baseUrl . '/accounts/' . $id;
            $response = Http::talkToMono($url);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getting ISA method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deletePSA(string $account_reference): array
    {
        try {

            $url = self::$baseUrl . '/payout-subaccounts/' . $account_reference;

            $response = Http::talkToMono($url, 'DELETE');

            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at delete PSA method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getBillers(): array
    {
        try {

            $url = self::$baseUrl . '/vas/services';
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getBillers method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getBillerById(string $id): array
    {
        try {
            $url = self::$baseUrl . '/vas/service/' . $id;
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getBillerById method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getBillerCategory(string $id): array
    {
        try {
            $url = self::$baseUrl . '/vas/service/' . $id . '/service-categories';
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getBillerCategory method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getBillerCategoryProduct(string $id): array
    {
        try {
            $url = self::$baseUrl . '/vas/service-category/' . $id . '/products';
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getBillerCategoryProduct method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyBillerCategoryNumber(string $id, string $number): array
    {
        try {
            $url = self::$baseUrl . '/vas/verify/';
            $data = [
                'serviceCategoryId' => $id,
                'entityNumber' => $number,
            ];
            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at verifyBillerCategoryNumber method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function purchaseService(array $data, string $service): array
    {
        try {
            $url = self::$baseUrl . '/vas/pay/' . $service;
            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at purchaseService method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPurchaseTransaction(string $id): array
    {
        try {
            $url = self::$baseUrl . '/vas/transaction/' . $id;
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at getPurchaseTransaction method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }
}