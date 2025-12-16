<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Province;
use Illuminate\Database\Eloquent\Builder;

class ProvinceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Province::class;
    }

    /**
     * Lấy query provinces.
     */
    public function queryProvinces(): Builder
    {
        return $this->model->query();
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        $sortBy = $sortBy ?? 'name';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $query->orderBy($sortBy, $direction);
    }
}


