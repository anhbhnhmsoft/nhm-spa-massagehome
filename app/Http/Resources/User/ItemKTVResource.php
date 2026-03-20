<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


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
        $onGoingBooking = $this->ktvBookings->first() ?? null;
        $data['on_going_booking'] = $onGoingBooking ? [
            'id' => $onGoingBooking->id,
            'start_time' => $onGoingBooking->start_time,
            'duration' => $onGoingBooking->duration,
            'expect_end_time' => Carbon::make($onGoingBooking->start_time)->copy()->addMinutes($onGoingBooking->duration),
        ] : null;
        $data['price_transportation'] = $this->priceTransportation;
        $files = $this->gallery;

        $data['display_image'] = $files->map(function ($file) {
            ;
            return [
                'id' => $file->id,
                'url' => $file->file_path ? Helper::getPublicUrl($file->file_path) : null,
            ];
        })->toArray();

        // Các lượt review gần đây
        $recentReviews = $this->reviewsReceived->map(function ($review) {
            $reviewerData = null;

            // Chú ý: Query của đã có where('hidden', false) nên không cần check lại !$review->hidden nữa
            $isVirtual = $review->is_virtual && !empty($review->virtual_name);

            if ($isVirtual) {
                // Xử lý review ảo
                $reviewerData = [
                    'id'   => "123456789", // Bạn có thể giữ nguyên ID ảo này hoặc hash một ID ngẫu nhiên
                    'name' => $review->virtual_name,
                    'avatar_url' => null,
                ];
            } else {
                // Xử lý review thật
                $reviewer = $review->reviewer ?? null;
                if ($reviewer) {
                    $reviewerData = [
                        'id' => $reviewer->id,
                        'name' => $reviewer->name,
                        'avatar_url' => $reviewer->profile?->avatar_url
                            ? Helper::getPublicUrl($reviewer->profile->avatar_url)
                            : null,
                    ];
                }
            }

            // Trả về cấu trúc của từng phần tử review
            return [
                'review_by'  => $reviewerData,
                'comment'    => $review->comment ?? '',
                'rating'     => $review->rating,
                'created_at' => $review->review_at ?? $review->created_at,
            ];
        });

        $data['recent_reviews'] = $recentReviews;

        $data['service_categories'] = $this->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'booking_count' => $category->pivot->performed_count ?? 0,
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
