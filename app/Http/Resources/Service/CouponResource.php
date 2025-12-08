<?php

namespace App\Http\Resources\Service;

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
            'for_service_id' => $this->for_service_id,
            'is_percentage' => $this->is_percentage,
            'discount_value' => $this->discount_value,
            'max_discount' => $this->max_discount,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
        ];
    }
}
