<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    /**
     * @var User
     */
    protected $model;

    public function getModel(): string
    {
        return User::class;
    }


    /**
     * Lấy query dành riêng cho KTV
     * @return Builder
     */
    public function queryKTV(): Builder
    {
        return $this->query()
            ->where('role', UserRole::KTV->value)
            ->where('is_active', true)
            ->with(['profile', 'reviewApplication', 'services'])
            // Tính trung bình Rating (chỉ lấy review không bị ẩn)
            ->withAvg(['reviewsReceived' => function (Builder $query) {
                    $query->where('hidden', false); // Quan trọng: Chỉ tính review công khai
                }], 'rating')
            ->withCount([
                // Đếm số lượng review (chỉ đếm review không bị ẩn)
                'reviewsReceived' => function (Builder $query) {
                    $query->where('hidden', false);
                },
                // Đếm số lượng job (chỉ đếm job đã hoàn thành)
                'jobsReceived' => function (Builder $query) {
                    $query->where('status', BookingStatus::COMPLETED->value);
                },
                'services'
            ]);
    }


    /**
     * Lọc query theo điều kiện
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo vai trò
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
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

    /**
     * Tìm user theo mã giới thiệu
     */
    public function findByReferralCode(string $code): ?User
    {
        return $this->query()->where('referral_code', $code)->first();
    }
}
