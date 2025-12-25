<?php

namespace App\Http\Resources\Service;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_featured' => $this->is_featured,
            'description' => $this->description,
            'image_url' => $this->image_url ? Helper::getPublicUrl($this->image_url) : null,
            'usage_count' => $this->usage_count,
        ];
    }
}
