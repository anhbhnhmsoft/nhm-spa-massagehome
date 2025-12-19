<?php

namespace App\Repositories;

use App\Core\BaseRepository;
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
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereColumn('used_count', '<', 'usage_limit');
                });
            // Lọc theo thời gian sử dụng (nếu có)
            $query->where(function ($q) use ($currentTime) {
                $q->whereRaw("(config->'allowed_time_slots') IS NULL")
                    ->orWhereRaw("jsonb_array_length(config->'allowed_time_slots') = 0")
                    ->orWhereRaw("EXISTS (
                SELECT 1 FROM jsonb_array_elements(config->'allowed_time_slots') AS slot
                WHERE ? >= (slot->>'start') AND ? <= (slot->>'end')
            )", [$currentTime, $currentTime]);
            });
        }

        // Lọc theo Dịch vụ (Logic: Lấy mã của dịch vụ này HOẶC mã toàn sàn)
        if (isset($filters['for_service_id'])) {
            $serviceId = $filters['for_service_id'];

            // Nhóm điều kiện lại: AND (service_id = X OR service_id IS NULL)
            $query->where(function ($q) use ($serviceId, $filters) {
                $q->where('for_service_id', $serviceId);

                // Nếu muốn lấy cả mã toàn sàn (Global) kèm theo
                if (isset($filters['get_all']) && filter_var($filters['get_all'], FILTER_VALIDATE_BOOLEAN)) {
                    $q->orWhereNull('for_service_id');
                }
            });
        } // Nếu không truyền service_id nhưng vẫn muốn lấy mã toàn sàn
        elseif (isset($filters['get_all']) && filter_var($filters['get_all'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNull('for_service_id');
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
}
