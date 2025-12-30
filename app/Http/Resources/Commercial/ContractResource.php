<?php

namespace App\Http\Resources\Commercial;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'slug' => $this->slug,
            'note' => $this->note,
            'file' => $this->path ? Helper::getPublicUrl($this->path) : null,
        ];
    }
}
