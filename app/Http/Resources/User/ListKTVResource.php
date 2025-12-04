<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListKTVResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;
        $reviewApplication = $this->reviewApplication;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'role' => $this->role,
            'language' => $this->language,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'rating' => $this->reviews_received_avg_rating ?? 0,
            'review_count' => $this->reviews_received_count ?? 0,
            'service_count' => $this->services_count ?? 0,
            'jobs_received_count' => $this->jobs_received_count ?? 0,
            'profile' => [
                'avatar_url' => $profile->avatar_url,
                'date_of_birth' => $profile->date_of_birth,
                'gender' => $profile->gender,
            ],
            'review_application' => [
                'address' => $reviewApplication->address,
                'skills' => $reviewApplication->skills,
                'experience' => $reviewApplication->experience,
                'latitude' => $reviewApplication->latitude,
                'longitude' => $reviewApplication->longitude,
                'bio' => $reviewApplication->bio,
            ],
        ];
    }
}
