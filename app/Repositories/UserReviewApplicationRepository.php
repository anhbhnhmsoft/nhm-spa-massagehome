<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserReviewApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserReviewApplicationRepository extends BaseRepository
{
    public function getModel(): string
    {
        return UserReviewApplication::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        // Lọc theo user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query;
    }

    /**
     * Sắp xếp query theo cột và hướng
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }
}
