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

    public function fetchTransactions (string $id, string $startDate, string $endDate)
    {
        try {
            $id = '6867e12dc41fad92989d140c';
            // $url = self::$baseUrl . '/accounts/' . $id . '/transactions?paginate=false';
            $url = self::$baseUrl . '/accounts/' . $id . '/transactions' . '?start=' . $startDate . '&end=' . $endDate . '&paginate=0';
            // dd($url);
            $response = Http::talkToMono($url, 'GET');
            return $response;
        } catch (Exception $e) {
            Log::error('Error Encountered at get transactions method in Mono Service: ' . $e->getMessage());
            throw $e;
        }
    }
}