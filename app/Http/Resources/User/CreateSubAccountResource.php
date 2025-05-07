<?php

namespace App\Http\Resources\User;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreateSubAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'account_type' => $this->account_type,
            'role' => Role::find($this->role_id)->name,
            'main_account_name' => $this->mainAccount->name,
            'main_account_username' => $this->mainAccount->username,
            'main_account_email' => $this->mainAccount->email,
        ];
    }
}
