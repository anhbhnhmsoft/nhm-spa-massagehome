<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ItemKTVResource extends ListKTVResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $files = $this->files;
        $review = $this->whenLoaded('reviewsReceived') ? $this->reviewsReceived->first() : null;
        $reviewer = $review ? $review->reviewer : null;
        $data['display_image'] = $files->map(function ($file) {;
            return [
                'id' => $file->id,
                'url' => Helper::FileUrl($file->file_path),
            ];
        })->toArray();
        $data['first_review'] = $review ? [
            'review_by' => [
                'id' => $reviewer->id,
                'name' => $reviewer->name,
                'avatar_url' => $reviewer->profile->avatar_url ? Storage::disk('public')->url($reviewer->profile->avatar_url) : null,
            ],
            'comment' => $review->comment,
            'rating' => $review->rating,
            'created_at' => $review->created_at,
        ]: null;
        return $data;
    }
}
