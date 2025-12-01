<?php

namespace App\Repositories;

use App\Core\BaseRepository;
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
     * Lọc query theo điều kiện
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filterQuery(Builder $query, array $filters): Builder
    {

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
