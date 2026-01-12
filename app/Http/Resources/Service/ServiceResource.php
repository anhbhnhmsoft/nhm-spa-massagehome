<?php

namespace App\Http\Resources\Service;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Chỉ dùng trả về item (ko dùng cho collection)
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->category;
        $provider = $this->provider;
        $prices = $this->category->prices;
        $rating = $this->reviews_avg_rating ?? 0;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'image_url' => $this->image_url ? Helper::getPublicUrl($this->image_url) : null,
            'bookings_count' => $this->bookings_count,
            'is_active' => (bool) $this->is_active,
            'avg_rating' => number_format($rating, 1),
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
            ],
            'options' => $prices->map(fn($price) => [
                'id' => $price->id,
                'duration' => $price->duration,
                'price' => $price->price,
            ]),
        ];
    }
}
