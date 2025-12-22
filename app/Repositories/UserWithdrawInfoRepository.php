<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserWithdrawInfo;
use Illuminate\Database\Eloquent\Builder;

class UserWithdrawInfoRepository extends BaseRepository
{
    public function getModel(): string
    {
        return UserWithdrawInfo::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo loại
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
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
        $column = $sortBy ?: 'created_at';
        $query->orderBy($column, $direction);
        return $query;
    }
}
