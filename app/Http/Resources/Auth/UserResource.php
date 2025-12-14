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
        $profile = $this->profile;
        $primary_location = $this->primaryAddress;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'disabled' => $this->disabled,
            'referral_code' => $this->referral_code,
            'role' => $this->role,
            'language' => $this->language,
            'referred_by_user_id' => $this->referred_by_user_id,
            'profile' => [
                'avatar_url' => $profile->avatar_url_full,
                'date_of_birth' => $profile->date_of_birth,
                'gender' => $profile->gender,
                'address' => $profile->address,
                'province_code' => $profile->province_code?->name ?? null,
                'district_code' => $profile->district_code?->name ?? null,
                'ward_code' => $profile->ward_code?->name ?? null,
                'bio' => $profile->bio,
            ],
            'primary_location' => $primary_location ? [
                'address' => $primary_location->address,
                'desc' => $primary_location->desc,
                'latitude' => $primary_location->latitude,
                'longitude' => $primary_location->longitude,
            ] : null,
        ];
    }
}
