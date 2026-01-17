<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Models\ServiceBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BookingRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return ServiceBooking::class;
    }

    /**
     * Lấy danh sách đặt lịch
     * @return Builder
     */
    public function queryBooking(): Builder
    {
        return $this->model->query()
            ->with([
                'user',
                'user.profile',
                'service',
                'ktvUser',
                'ktvUser.profile',
                'coupon'
            ]);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
            // Lọc theo số lượng đánh giá
            if (isset($filters['count_reviews_by_this_user_id']) && $filters['count_reviews_by_this_user_id'] === true) {
                $query->withCount(['reviews' => function (Builder $query) use ($filters) {
                    $query->where('review_by', $filters['user_id']);
                }]);
            }
        }

        if (isset($filters['ktv_user_id'])) {
            $query->where('ktv_user_id', $filters['ktv_user_id']);
        }


        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        if ($sortBy) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        return $query;
    }


    /**
     * Lấy số lượng khách hàng khác nhau đã đặt lịch trong khoảng thời gian
     * @param int $leadUserId - ID của KTV quản lý hoặc của Agency
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function countManagedKtvCustomerBookingTime(int $leadUserId, Carbon $from, Carbon $to): int
    {
        return $this->queryBooking()
            ->whereHas('ktvUser.reviewApplication', function ($query) use ($leadUserId) {
                $query->where('referrer_id', $leadUserId)
                    ->where('status', ReviewApplicationStatus::APPROVED->value)
                    ->where('role', UserRole::KTV->value);
            })
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::COMPLETED->value
            ])
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Lấy số lượng khách hàng khác nhau đã đặt lịch trong khoảng thời gian mà KH được giới thiệu bởi Affiliate
     * @param int $referrerId - ID của người giới thiệu
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function countReferredCustomerBookingTime(int $referrerId, Carbon $from, Carbon $to): int
    {
        return $this->queryBooking()
            ->whereHas('user', function ($query) use ($referrerId) {
                $query->where('referred_by_user_id', $referrerId)
                    ->where('role', UserRole::CUSTOMER->value);
            })
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::COMPLETED->value
            ])
            ->distinct('user_id')
            ->count('user_id');
    }
}
