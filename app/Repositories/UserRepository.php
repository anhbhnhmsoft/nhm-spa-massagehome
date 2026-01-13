<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
            ->with(['profile', 'reviewApplication', 'services','schedule']);

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
                ->where('service_bookings.status', BookingStatus::COMPLETED->value)
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

            // Thêm kiểm tra Join để tránh trùng lặp
            $query->when(
                !$query->getQuery()->joins || collect($query->getQuery()->joins)->pluck('table')->search('user_review_application') === false,
                fn ($q) => $q->leftJoin(
                    'user_review_application',
                    'users.id',
                    '=',
                    'user_review_application.user_id'
                )
            );

            // 2. Định nghĩa công thức tính khoảng cách (Distance)
            $distanceSelect = sprintf("
            (%s * acos(
                cos(radians(%s)) * cos(radians(user_review_application.latitude)) * cos(radians(user_review_application.longitude) - radians(%s))
                + sin(radians(%s)) * sin(radians(user_review_application.latitude))
            )) AS distance
        ", $earthRadiusKm, $targetLat, $targetLng, $targetLat);

            // 3. Thêm cột 'distance' vào kết quả trả về
            // CHÚ Ý: Phải select các cột chính của users.* để tránh bị trùng tên
            $query->selectRaw($distanceSelect);

            // 4. Sắp xếp theo cột 'distance' vừa tính được
            // Những User không có latitude/longitude sẽ có distance là NULL và bị đẩy xuống cuối.
            $query->orderBy('distance', 'asc');
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

    public function findByPhone(string $phone): ?User
    {
        return $this->query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();
    }
}
