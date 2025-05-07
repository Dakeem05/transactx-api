<?php

namespace App\Helpers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;

class TransactX
{

    /**
     * Return a new response from the application.
     */
    static function response(bool $success, string $message, int $code, object $data = null): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
