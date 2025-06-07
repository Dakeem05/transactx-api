<?php

namespace App\Http\Resources\User;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this['user']->username,
            'email' => $this['user']->email,
            'status' => $this['user']->status,
            'account_type' => $this['user']->account_type,
            'user_type' => $this['user']->user_type,
            'role' => Role::find($this['user']->role_id)->name,
            'token' => $this['token'],
        ];
    }
}
