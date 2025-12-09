<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Category::class;
    }

    public function queryCategory(): Builder
    {
        return $this
            ->query()
            ->where('is_active', true)
            ->orderByDesc('is_featured');
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * Mặc định sắp xếp theo position giảm dần
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        $query->orderBy('position', 'asc');
        return $query;
    }
}
