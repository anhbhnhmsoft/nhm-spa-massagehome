<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Models\ServiceBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                'ktvUser.reviewApplication',
                'coupon'
            ]);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        $statusFilter = isset($filters['status']) ? (int) $filters['status'] : null;

        // Lọc theo trạng thái
        if ($statusFilter && $statusFilter !== 0) {
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
            $shouldIncludeOpenApplications = isset($filters['include_available_open_for_application'])
                && is_array($filters['include_available_open_for_application'])
                && in_array($statusFilter, [null, 0, BookingStatus::OPEN_FOR_APPLICATION->value], true);

            if ($shouldIncludeOpenApplications) {
                $availableFilter = $filters['include_available_open_for_application'];
                $ktvId = (int) $filters['ktv_user_id'];
                $lat = isset($availableFilter['lat']) ? (float) $availableFilter['lat'] : null;
                $lng = isset($availableFilter['lng']) ? (float) $availableFilter['lng'] : null;
                $radius = isset($availableFilter['radius']) ? (int) $availableFilter['radius'] : 30000;

                $query->where(function (Builder $subQuery) use ($ktvId, $lat, $lng, $radius) {
                    $subQuery->where('ktv_user_id', $ktvId);

                    if ($lat === null || $lng === null) {
                        return;
                    }

                    $distanceFormula = $this->distanceSql('service_bookings.longitude', 'service_bookings.latitude', $lng, $lat);

                    $subQuery->orWhere(function (Builder $openQuery) use ($ktvId, $radius, $distanceFormula) {
                        $openQuery->where('service_bookings.status', BookingStatus::OPEN_FOR_APPLICATION->value)
                            ->whereNotNull('service_bookings.application_opened_at')
                            ->whereHas('service', function ($serviceQuery) use ($ktvId) {
                                $serviceQuery->whereHas('users', function ($userQuery) use ($ktvId) {
                                    $userQuery->where('users.id', $ktvId);
                                });
                            })
                            ->whereRaw("$distanceFormula <= ?", [$radius]);
                    });
                });
            } else {
                $query->where('ktv_user_id', $filters['ktv_user_id']);
            }
        }

        if (isset($filters['exclude_statuses']) && is_array($filters['exclude_statuses']) && !empty($filters['exclude_statuses'])) {
            $query->whereNotIn('status', $filters['exclude_statuses']);
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

    protected function distanceSql(string $lngColumn, string $latColumn, float $targetLng, float $targetLat): string
    {
        return sprintf(
            "ST_DistanceSphere(ST_MakePoint(%s::float, %s::float), ST_MakePoint(%F, %F))",
            $lngColumn,
            $latColumn,
            $targetLng,
            $targetLat
        );
    }

    /**
     * Lấy thông tin đặt lịch đang diễn ra trong ngày của user khách hàng
     * @param int $userId
     */
    public function getBookingCustomerOnGoingInDay(int $userId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::WAITING_KTV_CONFIRM->value,
                BookingStatus::OPEN_FOR_APPLICATION->value,
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
            ])
            ->where(function ($query) {
                $today = now()->toDateString(); // Lấy 'YYYY-MM-DD'
                $query->whereDate('booking_time', $today)
                    ->orWhereDate('start_time', $today);
            })
            ->first();
    }


    /**
     * Lấy thông tin đặt lịch theo id và trạng thái
     * @param int $bookingId
     * @param BookingStatus $status
     * @return ServiceBooking|null
     */
    public function getBookingByIdAndStatus(int $bookingId, BookingStatus $status): ServiceBooking|null
    {
        return $this->query()
            ->where('id', $bookingId)
            ->where('status', $status->value)
            ->first();
    }

    /**
     * Lấy thông tin dashboard đặt lịch của user khách hàng
     * @param int $userId
     * @return mixed[]
     */
    public function getBookingDashboardCustomer(int $userId)
    {
        $targetStatuses = [
            BookingStatus::PENDING->value,
            BookingStatus::WAITING_KTV_CONFIRM->value,
            BookingStatus::OPEN_FOR_APPLICATION->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::ONGOING->value,
        ];
        $bookingRawCounts = $this->query()
            ->where('user_id', $userId)
            ->whereIn('status', $targetStatuses) // Thêm lọc ở database để tối ưu
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($targetStatuses)
            ->mapWithKeys(function ($statusValue) use ($bookingRawCounts) {
                return [$statusValue => $bookingRawCounts->get($statusValue, 0)];
            })
            ->toArray();
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
            COUNT(CASE WHEN status = ? THEN 1 END) as pending,
            COUNT(CASE WHEN status = ? THEN 1 END) as confirmed,
            COUNT(CASE WHEN status = ? THEN 1 END) as ongoing,
            COUNT(CASE WHEN status = ? THEN 1 END) as completed,
            COUNT(CASE WHEN status = ? THEN 1 END) as waiting_cancel,
            COUNT(CASE WHEN status = ? THEN 1 END) as canceled,
            COUNT(CASE WHEN status = ? THEN 1 END) as payment_failed,
            COUNT(CASE WHEN status = ? THEN 1 END) as waiting_ktv_confirm,
            COUNT(CASE WHEN status = ? THEN 1 END) as open_for_application
        ", [
                BookingStatus::PENDING->value,
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::COMPLETED->value,
                BookingStatus::WAITING_CANCEL->value,
                BookingStatus::CANCELED->value,
                BookingStatus::PAYMENT_FAILED->value,
                BookingStatus::WAITING_KTV_CONFIRM->value,
                BookingStatus::OPEN_FOR_APPLICATION->value
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
     * (start_time + duration minutes + ? minutes) < now() (quá ? phút thì coi như là quá hạn)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkOverdueOnGoingBookings(int $minutes)
    {
        return $this->query()
            ->where('status', BookingStatus::ONGOING->value)
            ->whereNotNull('start_time')
            ->whereRaw(
                "start_time + (duration * interval '1 minute') + (? * interval '1 minute') < ?",
                [$minutes, now()]
            )
            ->get();
    }

    /**
     * Lấy danh sách booking ongoing đã quá ngưỡng tự hoàn thành.
     * @param int $minutes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAutoFinishOverdueOnGoingBookings(int $minutes)
    {
        return $this->query()
            ->where('status', BookingStatus::ONGOING->value)
            ->whereNotNull('start_time')
            ->whereRaw(
                "start_time + (duration * interval '1 minute') + (? * interval '1 minute') < ?",
                [$minutes, now()]
            )
            ->get();
    }

     /**
     * Lấy danh sách các booking đã xác nhận mà quá hạn, KTV vẫn chưa hoàn thành
     * @param int $minutes - Thời gian quá hạn (mặc định là 30 phút)
     * (booking_time + duration minutes + ? minutes) < now() (quá ? phút thì coi như là quá hạn)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkOverdueConfirmedBookings(int $minutes)
    {
        return $this->query()
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereNotNull('booking_time')
            ->whereRaw(
                "booking_time + (duration * interval '1 minute') + (? * interval '1 minute') < ?",
                [$minutes, now()]
            )
            ->get();
    }
}
