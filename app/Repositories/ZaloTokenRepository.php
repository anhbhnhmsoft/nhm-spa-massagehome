<?php

namespace App\Repositories;

use App\Core\BaseRepository;

use App\Models\ZaloToken;
use Illuminate\Database\Eloquent\Builder;

class ZaloTokenRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return ZaloToken::class;
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
