<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;

class UserAddressRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return UserAddress::class;
    }


    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo danh mục
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['is_primary'])) {
            $query->where('is_primary', $filters['is_primary']);
        }

        if (isset($filters['longitude'])) {
            $query->where('longitude', $filters['longitude']);
        }

        if (isset($filters['latitude'])) {
            $query->where('latitude', $filters['latitude']);
        }
        if (isset($filters['keywords'])) {
            $query->where('address', 'like', '%' . $filters['keywords'] . '%')->orWhere('desc', 'like', '%' . $filters['keywords'] . '%');
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
