<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\AffiliateEarning;
use Illuminate\Database\Eloquent\Builder;

class AffiliateEarningRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return AffiliateEarning::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
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
