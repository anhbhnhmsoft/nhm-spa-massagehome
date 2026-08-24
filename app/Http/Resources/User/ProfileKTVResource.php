<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileKTVResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $applycation = $this->getStaffReviewsAttribute()->first();
        $profile = $this->profile;
        $gallery = $this->gallery;
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $profile?->avatar_url ? Helper::getPublicUrl($profile->avatar_url) : null,
            'bio' => $applycation?->getTranslations('bio'),
            'experience' => $applycation?->experience,
            'gender' => $profile?->gender,
            'date_of_birth' => (string) $profile?->date_of_birth,
            'list_images' => $gallery->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'image_url' => $item->file_path ? Helper::getPublicUrl($item->file_path) : null,
                ];
            }),
            'contact_phone' => $applycation?->contact_phone,
            'contact_verified' => (bool) ($applycation?->contact_verified ?? false),
            'portrait_verified' => (bool) ($applycation?->portrait_verified ?? false),
            'portrait_verified_at' => $applycation?->portrait_verified_at?->toIso8601String(),
            'certificate_verified' => (bool) ($applycation?->certificate_verified ?? false),
            'certificates' => $applycation?->certificates ?? [],
            'techniques' => $applycation?->techniques ?? [],
            'strength_service_ids' => $applycation?->strength_service_ids ?? [],
            'province_code' => $applycation?->province_code,
            'district_code' => $applycation?->district_code,
            'ward_code' => $applycation?->ward_code,
            'priority_areas' => $applycation?->priority_areas ?? [],
            'service_locations' => $applycation?->service_locations ?? [],
        ];
    }
}
