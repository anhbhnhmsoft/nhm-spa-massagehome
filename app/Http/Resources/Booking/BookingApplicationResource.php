<?php

namespace App\Http\Resources\Booking;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ktv = $this->ktv;
        $profile = $ktv?->profile;
        $reviewApplication = $ktv?->reviewApplication;
        $primaryAddress = $ktv?->primaryAddress;

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'ktv_id' => $this->ktv_id,
            'status' => $this->status,
            'status_label' => $this->statusEnum()?->label(),
            'applied_at' => $this->applied_at,
            'selected_at' => $this->selected_at,
            'removed_reason' => $this->removed_reason,
            'ktv' => [
                'id' => $ktv?->id,
                'name' => $reviewApplication?->nickname ?? $ktv?->name,
                'phone' => $ktv?->phone,
                'avatar_url' => $profile?->avatar_url ? Helper::getPublicUrl($profile->avatar_url) : null,
                'experience' => $reviewApplication?->experience,
                'bio' => $reviewApplication?->bio,
                'location' => [
                    'address' => $primaryAddress?->address,
                    'latitude' => $primaryAddress?->latitude,
                    'longitude' => $primaryAddress?->longitude,
                ],
            ],
        ];
    }
}
