<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\StaticContract;
use Illuminate\Database\Eloquent\Builder;

class StaticContractRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return StaticContract::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

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
