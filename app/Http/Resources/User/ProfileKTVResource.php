<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'avatar_url' => $this->avatar_url ? Helper::getPublicUrl($this->avatar_url) : null,
            'bio' => $applycation?->getTranslations('bio'),
            'experience' => $applycation?->experience,
            'gender' => $profile?->gender,
            'date_of_birth' => (string) $profile?->date_of_birth,
            'lat' => (string) $applycation?->latitude,
            'lng' => (string) $applycation?->longitude,
            'address' => $applycation?->address,
            'list_images' => $gallery->map(function ($item) {
                return [
                    'id' => $item->id,
                    'image_url' => $item->file_path ? Helper::getPublicUrl($item->file_path) : null,
                ];
            }),
        ];
    }
}
