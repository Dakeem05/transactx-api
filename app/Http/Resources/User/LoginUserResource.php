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

        $data = parent::toArray($request);
        unset($data['referred_by_user_id']);

        return array_merge($data, [
            'role' => Role::find($this->role_id)->name,
        ]);
    }
}
