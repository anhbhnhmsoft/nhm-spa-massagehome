<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Builder;

class BannerRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return Banner::class;
    }

    public function queryBanner()
    {
        return $this->query()->where('is_active', true)
            ->orderBy('order', 'asc');
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
