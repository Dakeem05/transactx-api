<?php

namespace App\Http\Resources;

use App\Services\TransactXService;
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
        return [
            'tx_code' => TransactXService::get_tx_code_and_message($this['status_code'])['code'] ?? null,
            'tx_error' => TransactXService::get_tx_code_and_message($this['status_code'])['message'] ?? null,
            'data' => $this['message'],
        ];
    }
}
