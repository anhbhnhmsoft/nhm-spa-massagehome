<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\User;
use App\Models\UserReviewApplication;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserReviewApplicationRepository extends BaseRepository
{
    public function getModel(): string
    {
        return UserReviewApplication::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        // Lọc theo user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
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
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }

    /**
     * Đếm số KTV đã được duyệt mà một KTV referrer đã giới thiệu
     */
    public function getCountKtvReferrers(int|string $referrerId): int
    {
        return $this->query()
            ->join('users', 'user_review_application.user_id', '=', 'users.id')
            ->where('user_review_application.referrer_id', $referrerId)
            ->where('user_review_application.status', ReviewApplicationStatus::APPROVED->value)
            ->where('user_review_application.role', UserRole::KTV->value)
            ->where('users.role', UserRole::KTV->value)
            ->distinct('user_review_application.id')
            ->count('user_review_application.id');
    }

    /**
     * Đếm số lượng đơn hàng đang chờ duyệt của một KTV hoặc Agency
     */
    public function countPendingApplicationByRole(UserRole $role): int
    {
        return $this->query()
            ->where('role', $role->value)
            ->where('status', ReviewApplicationStatus::PENDING->value)
            ->count();
    }
}
