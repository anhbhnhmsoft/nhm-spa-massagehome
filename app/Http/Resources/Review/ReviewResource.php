<?php

namespace App\Http\Resources\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'user_id' => $this->user_id,
            'review_by' => $this->review_by,
            'service_booking_id' => $this->service_booking_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'hidden' => $this->hidden,
            'review_at' => $this->review_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'recipient' => $this->whenLoaded('recipient', function () {
                return [
                    'id' => $this->recipient->id,
                    'name' => $this->recipient->name,
                ];
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                ];
            }),
            'service_booking' => $this->whenLoaded('serviceBooking', function () {
                return [
                    'id' => $this->serviceBooking->id,
                    'service_id' => $this->serviceBooking->service_id,
                ];
            }),
        ];
    }
}

