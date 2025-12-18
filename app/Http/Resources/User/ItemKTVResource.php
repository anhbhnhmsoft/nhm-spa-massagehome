<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ItemKTVResource extends ListKTVResource
{

    private int $breakTimeGap;

    public function __construct($resource, $breakTimeGap = 0)
    {
        parent::__construct($resource);
        $this->breakTimeGap = $breakTimeGap;
    }

    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $booking = $this->ktvBookings?->first() ?? null;
        if ($this->breakTimeGap && $booking) {
            // Tính toán thời gian có thể đặt lịch sớm nhất của ktv
            $bookingTime = $booking->booking_time;
            $duration = $booking->duration;

            $bookingSoon = $bookingTime->copy()->addMinutes($duration + $this->breakTimeGap);
            $data['booking_soon'] = $bookingSoon->format('d/m/y H:i');
        } else {
            $data['booking_soon'] = null;
        }

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
