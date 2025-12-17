<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Coupon;
use App\Models\CouponUser;
use Illuminate\Database\Eloquent\Builder;

class CouponUserRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return CouponUser::class;
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
