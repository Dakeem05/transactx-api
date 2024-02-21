<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactXErrorResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $codeMap = [
            400 => [905, 'bad.request_error'],
            401 => [907, 'unauthenticated_error'],
            403 => [906, 'forbidden_error'],
            429 => [908, 'rate_limit_error'],
            500 => [909, 'internal_server_error'],
        ];

        return [
            'tx_code' => $codeMap[$this['status_code']][0] ?? null,
            'tx_error' => $codeMap[$this['status_code']][1] ?? null,
            'status_code' => $this['status_code'],
            'data' => $this['message'],
        ];
    }
}
