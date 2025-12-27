<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DetailServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $options = $this->options;
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'category_id' => $this->category_id,
            'description' => $this->getTranslations('description'),
            'image_url' => Storage::disk('public')->url($this->image_url),
            'bookings_count' => $this->bookings_count,
            'is_active' => $this->is_active,
            'options' => $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'duration' => $option->duration,
                    'price' => $option->price,
                ];
            }),
        ];
    }
}
