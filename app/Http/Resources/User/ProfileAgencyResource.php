<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileAgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $apply = $this->getAgencyReviewsAttribute()->first();
        $profile = $this->profile;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $profile->avatar_url ? Helper::getPublicUrl($profile->avatar_url) : null,
            'bio' => $apply?->getTranslations('bio'),
            'gender' => $profile?->gender,
            'date_of_birth' => (string) $profile?->date_of_birth,
        ];
    }
}
