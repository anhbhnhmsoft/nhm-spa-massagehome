<?php

namespace App\Http\Resources\Booking;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = $this->service;
        $ktvUser = $this->ktvUser;
        $ktvUserProfile = $this->ktvUser->profile;
        $user = $this->user;
        $userProfile = $this->user->profile;
        return [
            'id' => $this->id,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
            ],
            'ktv_user' => [
                'id' => $ktvUser->id,
                'name' => $ktvUser->name,
                'avatar_url' => $ktvUserProfile->avatar_url ? Helper::getPublicUrl($ktvUserProfile->avatar_url) : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $userProfile->avatar_url ? Helper::getPublicUrl($userProfile->avatar_url) : null,
            ],
            'address' => $this->address,
            'note_address' => $this->note_address,
            'lat' => (string)$this->latitude,
            'lng' => (string)$this->longitude,
            'booking_time' => $this->booking_time,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'note' => $this->note,
            'duration' => $this->duration,
            'status' => $this->status,
            'price' => $this->price,
            // Số lượng đánh giá
            'has_reviews' => $this->reviews_count > 0,
        ];
    }

}
