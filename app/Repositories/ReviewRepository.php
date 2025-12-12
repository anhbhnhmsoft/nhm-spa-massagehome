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
