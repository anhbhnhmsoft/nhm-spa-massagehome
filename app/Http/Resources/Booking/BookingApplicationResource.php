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
                'rating' => round((float) ($this->reviews_received_avg_rating ?? 0), 1),
                'review_count' => (int) ($this->reviews_received_count ?? 0),
                'location' => [
                    'address' => $primaryAddress?->address,
                    'latitude' => $primaryAddress?->latitude,
                    'longitude' => $primaryAddress?->longitude,
                ],
                'distance' => $this->distanceInMeters(),
            ],
        ];
    }

    private function distanceInMeters(): ?float
    {
        $booking = $this->booking;
        $address = $this->ktv?->primaryAddress;

        if (
            !$booking?->latitude
            || !$booking?->longitude
            || !$address?->latitude
            || !$address?->longitude
        ) {
            return null;
        }

        $earthRadius = 6371000;
        $bookingLat = deg2rad((float) $booking->latitude);
        $bookingLng = deg2rad((float) $booking->longitude);
        $ktvLat = deg2rad((float) $address->latitude);
        $ktvLng = deg2rad((float) $address->longitude);
        $deltaLat = $ktvLat - $bookingLat;
        $deltaLng = $ktvLng - $bookingLng;

        $a = sin($deltaLat / 2) ** 2
            + cos($bookingLat) * cos($ktvLat) * sin($deltaLng / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
