<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'phone' => Str::mask($this->phone, '*', 4, -3),
            'joined_at' => $affiliate ? $affiliate->updated_at->format('Y-m-d') : null,
        ];
    }
}
