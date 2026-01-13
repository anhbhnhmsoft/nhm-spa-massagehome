<?php

namespace App\Http\Resources\Commercial;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_url ? Helper::getPublicUrl($this->image_url) : null,
            'order' => $this->order,
        ];
    }
}
