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
            $query->where('is_active', true) // Bổ sung check active
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereColumn('used_count', '<', 'usage_limit');
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
        }
        // Nếu không truyền service_id nhưng vẫn muốn lấy mã toàn sàn
        elseif (isset($filters['get_all']) && filter_var($filters['get_all'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNull('for_service_id');
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

    public function incrementUsedCountAtomic(string $couponId): bool
    {
        return DB::transaction(function () use ($couponId) {
            $coupon = $this->model->query()
                ->where('id', $couponId)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereColumn('used_count', '<', 'usage_limit');
                })
                ->lockForUpdate()
                ->first();

            if (!$coupon) return false;

            $config = $coupon->config ?? [];
            $today = now()->format('Y-m-d');

            // Logic lưu lịch sử sử dụng thực tế (thanh toán thành công)
            $history = $config['daily_used'] ?? [];
            $history[$today] = ($history[$today] ?? 0) + 1;

            $config['daily_used'] = $history;

            return $coupon->update([
                'used_count' => $coupon->used_count + 1,
                'config' => $config
            ]);
        });
    }

    public function incrementDailyCollectCountAtomic(string $couponId): bool
    {
        return DB::transaction(function () use ($couponId) {
            $coupon = $this->model->query()
                ->where('id', $couponId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$coupon) return false;

            $config = $coupon->config ?? [];
            $today = now()->format('Y-m-d');

            $history = $config['daily_collected'] ?? [];
            $history[$today] = ($history[$today] ?? 0) + 1;

            $config['daily_collected'] = $history;

            return $coupon->update(['config' => $config]);
        });
    }
}
