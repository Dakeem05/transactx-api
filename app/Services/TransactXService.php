<?php

namespace App\Services;

class TransactXService
{
    /**
     * Get the corresponding tx code and message
     * @param int $status_code
     * @return array
     */
    public static function get_tx_code_and_message(int $status_code): array
    {
        $codeMap = [
            400 => [905, 'bad.request_error'],
            401 => [907, 'unauthenticated_error'],
            403 => [906, 'forbidden_error'],
            429 => [908, 'rate_limit_error'],
            500 => [909, 'internal_server_error'],
        ];

        return [
            "code" => $codeMap[$status_code][0],
            "message" => $codeMap[$status_code][1]
        ];
    }
}
