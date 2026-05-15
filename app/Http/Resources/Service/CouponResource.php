<?php

namespace App\Http\Resources\Service;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userRelation = $this->users->first();
        
        // Trạng thái đã sử dụng
        $isUsed = false;
        if ($this->user_id) {
            // Đối với mã tặng riêng
            $isUsed = $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
        } else if ($userRelation) {
            // Đối với mã chung đã thu thập
            $isUsed = (bool) $userRelation->pivot->is_used;
        }

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
            'is_collected' => (bool) ($userRelation || ($this->is_collected ?? false)),
            'is_used' => $isUsed,
            'display_ads' => $this->display_ads,
            'banners' => $this->banners ? Helper::getPublicUrl($this->banners) : null,
        ];
    }
}
