<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\AffiliateConfig;
use Illuminate\Database\Eloquent\Builder;

class AffiliateConfigRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return AffiliateConfig::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['target_role'])) {
            $query->where('target_role', $filters['target_role']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
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
