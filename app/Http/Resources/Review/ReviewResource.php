<?php

namespace App\Http\Resources\Review;

use App\Core\Helper;
use App\Enums\Language;
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
        $isVirtual = $this->is_virtual && !empty($this->virtual_name) && !$this->hidden;
        $reviewerData = null;
        // Nếu là đánh giá ảo, không có reviewer
        if ($isVirtual) {
            $reviewerData = [
                'id'   => "123456789",
                'name' => $this->virtual_name,
                'avatar' => null,
            ];
        } elseif (!$this->hidden) {
            $reviewer = $this->reviewer;
            $reviewerData = [
                'id' => $reviewer->id,
                'avatar' => $reviewer->profile?->avatar_url ? Helper::getPublicUrl($reviewer->profile->avatar_url) : null,
                'name' => $reviewer->name,
            ];
        }

        $translationComments = $this->getTranslations('comment_translated');

        $allTranslationComments = Helper::formatMultiLang($translationComments);
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'review_by' => $this->review_by ?? null,
            'comment_translated' => $allTranslationComments,
            'service_booking_id' => $this->service_booking_id ?? null,
            'rating' => $this->rating,
            'comment' => $this->comment ?? '',
            'review_at' => $this->review_at?->toISOString(),
            'reviewer' => $reviewerData,
        ];
    }
}

