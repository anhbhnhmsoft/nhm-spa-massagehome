<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReviewRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Review::class;
    }

    public function queryReview()
    {
        return $this->query()
            ->with(['reviewer', 'reviewer.profile']);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }
        if (isset($filters['hidden'])) {
            $query->where('hidden', $filters['hidden']);
        }
        if (isset($filters['service_booking_id'])) {
            $query->where('service_booking_id', $filters['service_booking_id']);
        }
        if (isset($filters['service_id'])) {
            $query->whereHas('serviceBooking', function (Builder $query) use ($filters) {
                $query->where('service_id', $filters['service_id']);
            });
        }
        if (isset($filters['review_by'])) {
            $query->where('review_by', $filters['review_by']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $column = $sortBy ?? 'created_at';
        $query->orderBy($column, $direction);
        return $query;
    }

    /**
     * Lấy tổng số review trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function countTotalReview(Carbon $from, Carbon $to)
    {
        return $this->query()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }
}
