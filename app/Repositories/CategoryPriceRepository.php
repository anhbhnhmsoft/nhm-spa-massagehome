<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\CategoryPrice;
use Illuminate\Database\Eloquent\Builder;

class CategoryPriceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return CategoryPrice::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        return $query;
    }
}
