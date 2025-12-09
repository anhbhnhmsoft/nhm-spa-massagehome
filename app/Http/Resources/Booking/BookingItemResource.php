<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->category;
        $provider = $this->provider;
        $options = $this->options;
        return [
            'id' => $this->id,
            'name' => $this->name,

        ];
    }

}
