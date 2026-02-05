<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\DangerSupport;
use Illuminate\Database\Eloquent\Builder;

class DangerSupportRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return DangerSupport::class;
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
