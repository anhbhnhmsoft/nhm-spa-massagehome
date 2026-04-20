<?php

namespace App\Http\Resources\Service;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'is_percentage' => $this->is_percentage,
            'discount_value' => $this->discount_value,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'is_collected' => (bool) ($this->is_collected ?? false),
            'display_ads' => $this->display_ads,
            'banners' => $this->banners ? Helper::getPublicUrl($this->banners) : null,
        ];
    }
}
