<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $affiliate = $this->affiliateRecords->first();

        return [
            'phone' => $this->phone,
            'joined_at' => $affiliate ? $affiliate->updated_at->format('Y-m-d') : null,
        ];
    }
}
