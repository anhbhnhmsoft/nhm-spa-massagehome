<?php

namespace App\Repositories;

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServiceOption;
use Illuminate\Database\Eloquent\Builder;

class ServiceOptionRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return ServiceOption::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo giá
        if (isset($filters['price'])) {
            $query->where('price', $filters['price']);
        }
        // lọc theo dịch vụ gốc
        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }
        // lọc theo thời lượng
        if (isset($filters['duration'])) {
            $query->where('duration', $filters['duration']);
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
