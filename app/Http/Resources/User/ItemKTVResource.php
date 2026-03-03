<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ItemKTVResource extends ListKTVResource
{

    private int $priceTransportation;

    public function __construct($resource, $priceTransportation = 0)
    {
        parent::__construct($resource);
        $this->priceTransportation = $priceTransportation;
    }

    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['price_transportation'] = $this->priceTransportation;
        $files = $this->gallery;
        $review = $this->whenLoaded('reviewsReceived') ? $this->reviewsReceived->first() : null;
        $reviewer = $review ? $review->reviewer : null;
        $data['display_image'] = $files->map(function ($file) {
            ;
            return [
                'id' => $file->id,
                'url' => $file->file_path ? Helper::getPublicUrl($file->file_path) : null,
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
        ] : null;

        $data['service_categories'] = $this->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'booking_count' => $category->booking_count ?? 0,
                'image_url' => $category->image_url ? Helper::getPublicUrl($category->image_url) : null,
                'prices' => $category->prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'price' => $price->price,
                        'duration' => $price->duration,
                    ];
                })->toArray(),
            ];
        })->toArray();
        return $data;
    }
}
