<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponUserResource extends JsonResource
{
    public function toArray($request): array
    {
        $coupon = $this->coupon;
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'coupon_id' => $this->coupon_id,
            'is_used' => $this->is_used,
            'coupon' => CouponResource::make($coupon),
        ];
    }
}
