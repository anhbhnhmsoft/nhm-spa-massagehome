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
     * Lấy thống kê đặt lịch trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     * @return \stdClass|null
     */
    public function getBookingStats(Carbon $from, Carbon $to)
    {
        return $this->query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending,
            COUNT(CASE WHEN status = ? THEN 1 END) as ongoing,
            COUNT(CASE WHEN status = ? THEN 1 END) as completed,
            COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as canceled
        ", [
                BookingStatus::PENDING->value,
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::COMPLETED->value,
                BookingStatus::CANCELED->value,
                BookingStatus::PAYMENT_FAILED->value
            ])
            ->first();
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
            ->where('status', BookingStatus::COMPLETED->value)
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
            ->where('status', BookingStatus::COMPLETED->value)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Lấy doanh thu tổng trong khoảng thời gian của KTV
     * @param int $ktvId - ID của KTV
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function getKtvTotalIncome(int $ktvId,Carbon $from, Carbon $to): int
    {
        return $this->query()
            ->where('ktv_user_id', $ktvId)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->where('status', BookingStatus::COMPLETED->value)
            ->sum('price');
    }

    /**
     * Lấy số lượng đơn đã đặt lịch KTV trong khoảng thời gian
     * @param int $ktvId - ID của KTV
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function getKtvTotalCustomerBooking(int $ktvId, Carbon $from, Carbon $to): int
    {
        return $this->query()
            ->where('ktv_user_id', $ktvId)
            ->where('status', BookingStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Lấy số lượng đơn đã đặt lịch KTV trong khoảng thời gian
     * @param int $ktvId - ID của KTV
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function getKtvTotalBooking(int $ktvId, Carbon $from, Carbon $to): int
    {
        return $this->query()
            ->where('ktv_user_id', $ktvId)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->where('status', BookingStatus::COMPLETED->value)
            ->count();
    }

    /**
     * Lấy danh sách các booking đang diễn ra mà quá hạn, KTV vẫn chưa hoàn thành
     * @param int $minutes - Thời gian quá hạn (mặc định là 30 phút)
     * (start_time + duration minutes + ? minutes) > now() (quá ? phút thì coi như là quá hạn)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkOverdueOnGoingBookings(int $minutes)
    {
        return $this->query()
            ->where('status', BookingStatus::ONGOING->value)
            ->whereNotNull('start_time')
            ->whereRaw(
                "start_time + (duration * interval '1 minute') + (? * interval '1 minute') > ?",
                [$minutes, now()]
            )
            ->get();
    }

     /**
     * Lấy danh sách các booking đã xác nhận mà quá hạn, KTV vẫn chưa hoàn thành
     * @param int $minutes - Thời gian quá hạn (mặc định là 30 phút)
     * (start_time + duration minutes + ? minutes) > now() (quá ? phút thì coi như là quá hạn)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkOverdueConfirmedBookings(int $minutes)
    {
        return $this->query()
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereNotNull('booking_time')
            ->whereRaw(
                "booking_time + (duration * interval '1 minute') + (? * interval '1 minute') > ?",
                [$minutes, now()]
            )
            ->get();
    }
}
