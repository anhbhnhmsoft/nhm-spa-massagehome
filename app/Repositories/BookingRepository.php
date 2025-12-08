<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServiceBooking;
use Illuminate\Database\Eloquent\Builder;

class BookingRepository extends BaseRepository
{

    public function getModel(): string
    {
        return ServiceBooking::class;
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
