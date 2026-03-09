<?php

namespace App\Http\Resources\Service;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Chỉ dùng trả về item (ko dùng cho collection)
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_featured' => $this->is_featured,
            'description' => $this->description,
            'image_url' => $this->image_url ? Helper::getPublicUrl($this->image_url) : null,
            'is_registered' => (bool)$this->is_registered,
            'is_active' => (bool) $this->is_active,
            'total_bookings' => $this->bookings_count ?? 0,
            'prices' => $this->prices->map(fn($price) => [
                'duration' => $price->duration,
                'price' => $price->price,
            ]) ?? [],
        ];
    }
}
