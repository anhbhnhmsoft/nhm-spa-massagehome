<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\CouponUsed;
use Illuminate\Database\Eloquent\Builder;

class CouponUsedRepository extends BaseRepository
{
    public function getModel(): string
    {
        return CouponUsed::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo ID
        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
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
