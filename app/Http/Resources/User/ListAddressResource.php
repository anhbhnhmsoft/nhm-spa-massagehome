<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'desc' => $this->desc,
            'is_primary' => $this->is_primary,
        ];
    }
}
