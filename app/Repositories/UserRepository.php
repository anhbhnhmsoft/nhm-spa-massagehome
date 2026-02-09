<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Core\Helper;
use App\Enums\BookingStatus;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return User::class;
    }

    /**
     * Lấy query dành cho user chung
     * @return Builder
     */
    public function queryUser()
    {
        return $this->query()
            ->where('is_active', true);
    }

    /**
     * Lấy query dành riêng cho KTV
     * @return Builder
     */
    public function queryKTV(): Builder
    {
        $query = $this->query()
            ->where('users.role', UserRole::KTV->value)
            ->where('users.is_active', true)
            // Chỉ lấy KTV đã được duyệt
            ->whereHas('reviewApplication', function ($q) {
                $q->where('status', ReviewApplicationStatus::APPROVED->value);
            })
            ->with(['profile', 'reviewApplication', 'services','schedule','primaryAddress']);

        // Tính Trung bình Rating và Đếm Reviews
        $query->leftJoinSub(
            Review::query()->selectRaw('user_id, AVG(rating) as reviews_received_avg_rating, COUNT(id) as reviews_received_count')
                ->where('hidden', false)
                ->groupBy('user_id'),
            'review_stats',
            'review_stats.user_id',
            '=',
            'users.id'
        )
            ->addSelect([
                'reviews_received_avg_rating' => 'review_stats.reviews_received_avg_rating',
                'reviews_received_count' => 'review_stats.reviews_received_count',
            ]);

        // Đếm Số lượng Jobs đã hoàn thành
        $query->leftJoinSub(
            ServiceBooking::selectRaw('services.user_id, COUNT(service_bookings.id) as jobs_received_count')
                ->join('services', 'services.id', '=', 'service_bookings.service_id')
                ->groupBy('services.user_id'),
            'job_stats',
            'job_stats.user_id',
            '=',
            'users.id'
        )
            ->addSelect([
                'jobs_received_count' => 'job_stats.jobs_received_count',
            ]);


        // Đếm Số lượng Services (Services)
        $query->leftJoinSub(
            Service::selectRaw('user_id, COUNT(id) as services_count')
                ->groupBy('user_id'),
            'service_stats',
            'service_stats.user_id',
            '=',
            'users.id'
        )
            ->addSelect([
                'services_count' => 'service_stats.services_count',
            ]);


        // Đảm bảo lấy tất cả các cột của bảng users
        $query->addSelect('users.*');

        return $query;
    }


    /**
     * Lọc query theo điều kiện
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo từ khóa
        if (isset($filters['keyword']) && !empty(trim($filters['keyword']))) {
            $keyword = trim($filters['keyword']);
            $query->whereRaw("unaccent(name) ILIKE unaccent(?)", ["%{$keyword}%"]);
        }
        // Lọc theo vai trò
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (isset($filters['referrer_id'])) {
            $referrer_id = $filters['referrer_id'];
            $query->whereHas('reviewApplication', function ($q) use ($referrer_id) {
                $q->where('referrer_id', $referrer_id);
            });
        }

        // Lọc và Sắp xếp theo Vị trí
        if (isset($filters['lat'], $filters['lng']) && is_numeric($filters['lat']) && is_numeric($filters['lng'])) {
            $targetLat = (float) $filters['lat'];
            $targetLng = (float) $filters['lng'];
            $earthRadiusKm = 6371; // Bán kính Trái Đất tính bằng Km

            // LeftJoin lấy cả người không có địa chỉ (distance sẽ là NULL)
            $query->leftJoin('user_address', function($join) {
                $join->on('users.id', '=', 'user_address.user_id')
                    ->where('user_address.is_primary', true);
            });

            // Định nghĩa công thức tính khoảng cách (Distance)
            $distanceSelect = sprintf("
                (ST_DistanceSphere(
                    ST_MakePoint(user_address.longitude::float, user_address.latitude::float),
                    ST_MakePoint(%f, %f)
                ) / 1000) AS distance
            ", $targetLng, $targetLat);

            // Select dữ liệu
            // Quan trọng: Phải select users.* để không bị mất dữ liệu user sau khi join
            $query->addSelect('users.*');
            $query->selectRaw($distanceSelect);

            // 4. Sắp xếp theo khoảng cách
            $query->orderByRaw('distance ASC NULLS LAST');
        }

        return $query;
    }

    /**
     * Sắp xếp query theo cột và hướng
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        switch ($sortBy) {
            case 'rating':
                $column = 'reviews_received_avg_rating';
                break;
            case 'review_count':
                $column = 'reviews_received_count';
                break;
            default:
                $column = 'created_at';
                break;
        }
        $query->orderBy($column, $direction);
        return $query;
    }

    /**
     * Kiểm tra SĐT đã tồn tại và đã xác thực chưa
     * @param string $phone
     * @return bool
     */
    public function isPhoneVerified(string $phone): bool
    {
        return $this->query()->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->exists();
    }

    /**
     * Tìm kiếm User theo SĐT đã xác thực
     * @param string $phone
     * @return User|null
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();
    }

    /**
     * Số lượng Khách hàng đã giới thiệu trong khoảng thời gian
     * @param int $referrerId
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function countReferralCustomers(int $referrerId, Carbon $from, Carbon $to): int
    {
        return $this->queryUser()
            ->where('referred_by_user_id', $referrerId)
            ->where('role', UserRole::CUSTOMER->value)
            ->whereBetween('referred_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->count();
    }

    /**
     * Lấy danh sách KTV và số lượng đơn hoàn thành, doanh thu trong khoảng thời gian
     * @param int $leadUserId
     * @param Carbon $from
     * @param Carbon $to
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getKtvPerformancePaginated(int $leadUserId, Carbon $from, Carbon $to, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $fromDate = $from->format('Y-m-d H:i:s');
        $toDate = $to->format('Y-m-d H:i:s');

        $query = $this->query()
            ->where('users.role', UserRole::KTV->value)
            ->where('users.is_active', true)
            ->join('user_review_application', 'users.id', '=', 'user_review_application.user_id')
            ->where('user_review_application.referrer_id', $leadUserId)
            ->leftJoin('service_bookings', function($join) use ($fromDate, $toDate) {
                $join->on('users.id', '=', 'service_bookings.ktv_user_id')
                    ->where('service_bookings.status', BookingStatus::COMPLETED->value)
                    ->whereBetween('service_bookings.booking_time', [$fromDate, $toDate]);
            })
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->select([
                'users.id',
                'users.name',
                'users.phone',
                'user_profiles.avatar_url',
                DB::raw('COUNT(service_bookings.id) as total_finished_bookings'),
                DB::raw('COALESCE(SUM(service_bookings.price), 0) as total_revenue'),
                DB::raw('COUNT(DISTINCT service_bookings.user_id) as total_unique_customers')
            ])
            ->groupBy('users.id', 'users.name', 'users.phone', 'user_profiles.avatar_url')
            ->orderByDesc('total_revenue');

        // Sử dụng paginate thay vì get
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Xử lý dữ liệu cho từng item trong trang hiện tại (transform link ảnh)
        $paginator->getCollection()->transform(function ($item) {
            $item->avatar_url = $item->avatar_url
                ? Helper::getPublicUrl($item->avatar_url)
                : null;
            return $item;
        });

        return $paginator;
    }

    /**
     * Lấy số lượng người dùng theo vai trò
     * @param UserRole $role
     * @return int
     */
    public function countUserByRole(UserRole $role): int
    {
        return $this->queryUser()
            ->where('role', $role->value)
            ->count();
    }


}
