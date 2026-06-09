<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingApplicationStatus;
use App\Models\BookingApplication;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

class BookingApplicationRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return BookingApplication::class;
    }

    public function queryWithRelations(): Builder
    {
        return $this->query()
            ->leftJoinSub(
                Review::query()
                    ->selectRaw('user_id, AVG(rating) as reviews_received_avg_rating, COUNT(id) as reviews_received_count')
                    ->where('hidden', false)
                    ->groupBy('user_id'),
                'review_stats',
                'review_stats.user_id',
                '=',
                'booking_applications.ktv_id'
            )
            ->addSelect('booking_applications.*')
            ->addSelect([
                'reviews_received_avg_rating' => 'review_stats.reviews_received_avg_rating',
                'reviews_received_count' => 'review_stats.reviews_received_count',
            ])
            ->with([
                'booking',
                'booking.user.profile',
                'booking.service',
                'booking.ktvUser.profile',
                'ktv.profile',
                'ktv.reviewApplication',
                'ktv.schedule',
                'ktv.primaryAddress',
            ]);
    }

    public function activeApplicationStatuses(): array
    {
        return [
            BookingApplicationStatus::APPLIED->value,
        ];
    }
}
