<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Services\TransactXService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'tx_message' => TransactXService::get_tx_code_and_message($this['status_code'])['message'] ?? null,
            'data' => [
                'username' => $this['data']->username,
                'email' => $this['data']->email,
                'referral_code' => $this['data']->referral_code,
                'status' => $this['data']->status,
                'avatar' => $this['data']->avatar,
                'role' => Role::find($this['data']->role_id)->name,
            ]
        ];
    }
}
