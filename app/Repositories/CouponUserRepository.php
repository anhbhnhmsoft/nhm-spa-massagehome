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
    public function queryCouponUser(): Builder
    {
        return $this->model->query()
            ->with(['coupon']);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['is_used'])) {
            $query->where('is_used', $filters['is_used']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        return $query;
    }
}
