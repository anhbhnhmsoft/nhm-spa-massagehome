<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'position' => $this->position,
            'is_active' => $this->is_active,
        ];
    }
}
