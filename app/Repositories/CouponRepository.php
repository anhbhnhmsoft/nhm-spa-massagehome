<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CouponRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Coupon::class;
    }

    public function queryCoupon(?string $userId = null): Builder
    {
        $query = $this->model
            ->query()
            ->where('is_active', true);

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });
        } else {
            // Nếu không có userId, thường là lấy mã công khai
            $query->whereNull('user_id');
        }
        $query->where('code', '!=', 'WELCOME');

        return $query;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo ID
        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }
        // Lọc theo mã giảm giá (Search)
        if (!empty($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        // Lọc các mã "Có hiệu lực"
        // Sử dụng filter_var để chấp nhận cả 'true', '1', true, 1
        if (isset($filters['is_valid']) && filter_var($filters['is_valid'], FILTER_VALIDATE_BOOLEAN)) {
            $now = Carbon::now();
            // Lọc theo thời gian hiệu lực
            $query->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereColumn('used_count', '<', 'usage_limit');
                });
        }

        // Lọc theo Người dùng (Logic: Lấy mã mà người dùng này chưa sử dụng)
        if (isset($filters['user_id_is_not_used'])) {
            $userId = $filters['user_id_is_not_used'];
            $query->where(function ($q) use ($userId) {
                // 1. Nếu là mã tặng riêng (user_id IS NOT NULL) -> Kiểm tra chính nó chưa dùng hết limit
                $q->where(function ($sq) use ($userId) {
                    $sq->where('user_id', $userId)
                        ->where(function ($sq2) {
                            $sq2->whereNull('usage_limit')
                                ->orWhereColumn('used_count', '<', 'usage_limit');
                        });
                })
                // 2. Nếu là mã chung -> Kiểm tra bảng coupon_users xem user này đã dùng chưa
                ->orWhere(function ($sq) use ($userId) {
                    $sq->whereNull('user_id')
                        ->whereDoesntHave('users', function ($sq2) use ($userId) {
                            $sq2->where('user_id', $userId)
                                ->where('is_used', true);
                        });
                });
            });
        }

        // Lọc các mã mà user đã sở hữu (bao gồm đã thu thập hoặc được tặng riêng)
        if (isset($filters['is_owned'])) {
            $userId = $filters['is_owned'];
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('users', function ($sq) use ($userId) {
                        $sq->where('user_id', $userId);
                    });
            });
        }

        // Lọc các mã mà user chưa thu thập (chỉ áp dụng cho mã chung)
        if (isset($filters['user_id_is_not_collected'])) {
            $userId = $filters['user_id_is_not_collected'];
            $query->whereNull('user_id') // Chỉ xét mã chung
                ->whereDoesntHave('users', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        }

        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $column = $sortBy ?? 'created_at';
        $query->orderBy($column, $direction);
        return $query;
    }

    public function getCouponByIdOrFail(int $couponId, bool $lockForUpdate = false): ?Coupon
    {
        $query = $this->queryCoupon()
            ->where('id', $couponId);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        return $query->first();
    }
}
