<?php

namespace App\Http\Resources\Review;

use App\Core\Helper;
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
            'reviewer' =>  $this->hidden ? null : $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'avatar' => $this->reviewer->profile?->avatar_url ? Helper::getPublicUrl($this->reviewer->profile->avatar_url) : null,
                    'name' => $this->reviewer->name,
                ];
            }),
        ];
    }
}

