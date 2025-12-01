<?php

namespace App\Http\Resources\Auth;

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
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'disabled' => $this->disabled,
            'referral_code' => $this->referral_code,
            'role' => $this->role,
            'language' => $this->language,
            'referred_by_user_id' => $this->referred_by_user_id,
        ];
    }
}
