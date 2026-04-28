<?php

namespace App\Http\Resources\Booking;

use App\Core\Helper;
use App\Core\Helper\CalculatePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = $this->service;
        $ktvUser = $this->ktvUser;
        $ktvUserProfile = $this->ktvUser->profile;
        $ktvUserReviewApplication = $this->ktvUser->reviewApplication;
        $user = $this->user;
        $userProfile = $this->user->profile;
        $coupon = $this->coupon ?? null;

        $price = (float)($this->price ?? 0);
        $priceDiscount = (float)($this->price_discount ?? 0);
        $priceTransportation = (float)($this->price_transportation ?? 0);
        $totalPrice = CalculatePrice::totalBookingPrice(
            price: $price,
            priceDiscount: $priceDiscount,
            priceTransportation: $priceTransportation,
        );

        return [
            'id' => $this->id,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'image' => $service->image_url ? Helper::getPublicUrl($service->image_url) : null,
            ],
            'ktv_user' => [
                'id' => $ktvUser->id,
                'name' => $ktvUserReviewApplication->nickname ?? "",
                'avatar_url' => $ktvUserProfile->avatar_url ? Helper::getPublicUrl($ktvUserProfile->avatar_url) : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $userProfile->avatar_url ? Helper::getPublicUrl($userProfile->avatar_url) : null,
                'phone' => $user->phone ?? null,
            ],
            'address' => $this->address,
            'lat' => (string)$this->latitude,
            'lng' => (string)$this->longitude,
            'booking_time' => $this->booking_time,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'note' => $this->note,
            'duration' => $this->duration,
            'status' => $this->status,
            'price' => $price,
            'price_discount' => $priceDiscount,
            'price_transportation' => $priceTransportation,
            'total_price' => $totalPrice,
            'coupon' => $coupon ? [
                'id' => $coupon->id,
                'label' => $coupon->label,
            ] : null,
            // Số lượng đánh giá
            'has_reviews' => $this->reviews_count > 0,
            'reason_cancel' => (string)($this->reason_cancel ?? null),
        ];
    }

}
