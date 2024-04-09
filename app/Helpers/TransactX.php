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
            'tx_code' => TransactXService::getCodeAndMessage($code)['code'],
            'tx_message' => TransactXService::getCodeAndMessage($code)['message'],
            'data' => $data
        ], $code);
    }
}
