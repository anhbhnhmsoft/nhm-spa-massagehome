<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\GeoCachingPlace;
use Illuminate\Database\Eloquent\Builder;

class GeoCachingPlaceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return GeoCachingPlace::class;
    }
    /**
     * Khởi tạo query
     * @return Builder
     */
    public function query(): Builder
    {
        return $this->model->query();
    }
    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['keyword'])) {
            $query->where('keyword', 'like', "%{$filters['keyword']}%");
        }
        return $query;
    }

    public function findByPlaceId(string $placeId): ?GeoCachingPlace
    {
        return $this->query()->where('place_id', $placeId)->first();
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
