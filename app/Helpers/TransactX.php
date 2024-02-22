<?php

namespace App\Helpers;

use App\Services\TransactXService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;

class TransactX
{

    /**
     * Return a new response from the application.
     */
    static function response(mixed $data, int $code): JsonResponse
    {
        return response()->json([
            'tx_code' => TransactXService::get_tx_code_and_message($code)['code'],
            'tx_message' => TransactXService::get_tx_code_and_message($code)['message'],
            'data' => $data
        ], $code);
    }
}
