<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Builder;

class UserDeviceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return UserDevice::class;
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
