<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

class ReviewRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Review::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }
        if (isset($filters['hidden'])) {
            $query->where('hidden', $filters['hidden']);
        }
        if (isset($filters['service_booking_id'])) {
            $query->where('service_booking_id', $filters['service_booking_id']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
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
}
