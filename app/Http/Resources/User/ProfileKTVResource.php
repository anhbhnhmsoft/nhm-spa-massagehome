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
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $profile->avatar_url ? Helper::getPublicUrl($profile->avatar_url) : null,
            'bio' => $applycation?->getTranslations('bio'),
            'experience' => $applycation?->experience,
            'gender' => $profile?->gender,
            'date_of_birth' => (string) $profile?->date_of_birth,
            'list_images' => $gallery->map(function ($item) {
                return [
                    'id' => $item->id,
                    'image_url' => $item->file_path ? Helper::getPublicUrl($item->file_path) : null,
                ];
            }),
        ];
    }
}
