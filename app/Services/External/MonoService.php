<?php

namespace App\Services\External;

use App\Models\LinkedBankAccount;
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

    public function linkAccount (User $user, string $reference)
    {
        try {
            $url = self::$baseUrl . '/accounts/initiate';
            
            $data = [
                'customer' => [
                    "name" => $user->name,
                    "email" => $user->email,
                ],
                'meta' => [
                    'ref' => $reference,
                ],
                'scope' => "auth",
                'redirect_url' => self::$callbackUrl,
            ];

            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at link account method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function relinkAccount (LinkedBankAccount $account)
    {
        try {
            $url = self::$baseUrl . '/accounts/initiate';
            
            $data = [
                'meta' => [
                    'ref' => $account->reference,
                ],
                'scope' => "reauth",
                'account' => $account->account_id,
                'redirect_url' => self::$callbackUrl,
            ];

            $response = Http::talkToMono($url, 'POST', $data);
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at relink account method in Mono Service: ' . $e->getMessage());
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