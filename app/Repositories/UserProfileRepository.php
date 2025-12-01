<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;

class UserProfileRepository extends BaseRepository
{
    public function getModel(): string
    {
        return UserProfile::class;
    }

    /**
     * Lọc query theo điều kiện
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filterQuery(Builder $query, array $filters): Builder
    {
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
        $column = $sortBy ?? 'created_at';
        $query->orderBy($column, $direction);
        return $query;
    }
}
