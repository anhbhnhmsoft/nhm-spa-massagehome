<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Core\Service\ServiceException;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Coupon::class;
    }

    public function queryCoupon(): Builder
    {
        return $this->model
            ->query()
            ->where('is_active', true);
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
            $currentTime = $now->copy()->format('H:i');
            // Lọc theo thời gian hiệu lực
            $query->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->whereColumn('used_count', '<', 'usage_limit');
        }

        // Lọc theo Người dùng (Logic: Lấy mã mà người dùng này chưa sử dụng)
        if (isset($filters['user_id_is_not_used'])) {
            $userId = $filters['user_id_is_not_used'];
            // Logic: Lấy coupon mà KHÔNG CÓ (quan hệ với user này VÀ trạng thái là đã dùng)
            // Tức là:
            // 1. Coupon chưa thu thập -> Thỏa mãn (Vì không có quan hệ)
            // 2. Coupon đã thu thập nhưng chưa dùng -> Thỏa mãn (Vì quan hệ có is_used = false)
            // 3. Coupon đã thu thập và đã dùng -> Bị loại (Vì khớp điều kiện bên trong)
            $query->whereDoesntHave('users', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('is_used', true); // Chú ý: Ở đây ta filter những cái ĐÃ DÙNG để loại bỏ nó
            });
        }

        // Lọc các mã mà user chưa thu thập
        if (isset($filters['user_id_is_not_collected'])) {
            $userId = $filters['user_id_is_not_collected'];
            $query->whereDoesntHave('users', function ($q) use ($userId) {
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
