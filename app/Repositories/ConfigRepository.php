<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Config;
use Illuminate\Database\Eloquent\Builder;

class ConfigRepository extends BaseRepository
{
    public function getModel(): string
    {
        return Config::class;
    }








    /**
     * Không cần thiết, chỉ áp dụng cho đúng với quy định của repository
     */
    public function filterQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }
    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        return $query;
    }
}
