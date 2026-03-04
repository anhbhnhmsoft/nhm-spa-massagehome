<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\DangerSupportStatus;
use App\Enums\DateRangeDashboard;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Enums\WalletTransactionStatus;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\DangerSupportRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService extends BaseService
{
    public function __construct(
        protected BookingRepository               $bookingRepository,
        protected UserRepository                  $userRepository,
        protected UserReviewApplicationRepository $userReviewApplicationRepository,
        protected WalletTransactionRepository     $walletTransactionRepository,
        protected ReviewRepository                $reviewRepository,
        protected WalletRepository                $walletRepository,
        protected DangerSupportRepository         $dangerSupportRepository,
    ) {
        parent::__construct();
    }
    /**
     * Lấy thống kê tổng quan (dashboard admin)
     * @return ServiceReturn
     */
    public function getGeneralStats(DateRangeDashboard $dateRange): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = $dateRange->getDateRange();
            $start = $dateRange['from'];
            $end = $dateRange['to'];

            // Lấy thống kê doanh thu và lợi nhuận
            $incomeOutcome = $this->walletTransactionRepository->getFinancialInOutStats($start, $end);

            // Lấy thống kê doanh thu và chi phí
            $revenue = $this->walletTransactionRepository->getFinancialDashboardStats($start, $end);

            return ServiceReturn::success([
                'system_inout' =>[
                    'total_income' => (float) ($incomeOutcome->total_income ?? 0),
                    'total_outcome' => (float) ($incomeOutcome->total_outcome ?? 0),
                ],
                'revenue' => [
                    'total_revenue' => (float) $revenue->total_revenue,
                    'operation_cost' => (float) $revenue->operation_cost,
                    'profit' => round($revenue->total_revenue - $revenue->operation_cost, 2),
                    'technical_cost' => (float) $revenue->technical_cost,
                    'customer_cost' => (float) $revenue->customer_cost,
                    'transportation_cost' => (float) $revenue->transportation_cost,
                    'agency_cost' => (float) $revenue->agency_cost,
                ],
            ]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi DashboardService@getGeneralStats",
                ex: $exception,
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Lấy thống kê tổng quan về đơn hàng (dashboard admin)
     * @return ServiceReturn
     */
    public function getGeneralBookingStats(DateRangeDashboard $dateRange): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = $dateRange->getDateRange();
            $start = $dateRange['from'];
            $end = $dateRange['to'];

            $stats = $this->bookingRepository->getBookingStats($start, $end);

            // Tổng số đơn hàng
            $totalBooking     = $stats->total ?? 0;
            // Số đơn hàng đang chờ
            $pendingBooking   = $stats->pending ?? 0;
            // Số đơn hàng đã xác nhận
            $confirmedBooking = $stats->confirmed ?? 0;
            // Số đơn hàng đang tiến hành
            $ongoingBooking   = $stats->ongoing ?? 0;
            // Số đơn hàng đã hoàn thành
            $completedBooking = $stats->completed ?? 0;
            // Số đơn hàng đang chờ hủy
            $waitingCancelBooking = $stats->waiting_cancel ?? 0;
            // Số đơn hàng đã hủy và hoàn tiền
            $canceledBooking  = $stats->canceled ?? 0;
            // Số đơn hàng bị lỗi
            $paymentFailedBooking = $stats->payment_failed ?? 0;

            return ServiceReturn::success([
                'total_booking' => $totalBooking,
                'pending_booking' => $pendingBooking,
                'confirmed_booking' => $confirmedBooking,
                'ongoing_booking' => $ongoingBooking,
                'completed_booking' => $completedBooking,
                'waiting_cancel_booking' => $waitingCancelBooking,
                'canceled_booking' => $canceledBooking,
                'payment_failed_booking' => $paymentFailedBooking,
            ]);

        }catch (\Exception $exception){
            LogHelper::error(
                message: "Lỗi DashboardService@getGeneralBookingStats",
                ex: $exception,
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Lấy thống kê tổng quan về người dùng (dashboard admin)
     * @return ServiceReturn
     */
    public function getGeneralUserStats(DateRangeDashboard $dateRange): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = $dateRange->getDateRange();
            $start = $dateRange['from'];
            $end = $dateRange['to'];

            // Tổng số KTV
            $totalKtv = $this->userRepository->countUserByRole(UserRole::KTV);
            // Số KTV đang chờ duyệt
            $pendingKtv = $this->userReviewApplicationRepository->countPendingApplicationByRole(UserRole::KTV);
            // Tổng số Agency
            $totalAgency = $this->userRepository->countUserByRole(UserRole::AGENCY);
            // Số Agency đang chờ duyệt
            $pendingAgency = $this->userReviewApplicationRepository->countPendingApplicationByRole(UserRole::AGENCY);
            // Tổng số Customer
            $totalCustomer = $this->userRepository->countUserByRole(UserRole::CUSTOMER);
            // Yêu cầu rút tiền đang chờ duyệt
            $withdrawRequests = $this->walletTransactionRepository->countTotalWithdrawPendingRequestTransaction($start, $end);
            // Tổng số Review
            $reviewCount = $this->reviewRepository->countTotalReview($start, $end);

            return ServiceReturn::success([
                'total_ktv' => $totalKtv,
                'pending_ktv' => $pendingKtv,
                'total_agency' => $totalAgency,
                'pending_agency' => $pendingAgency,
                'total_customer' => $totalCustomer,
                'withdraw_requests' => $withdrawRequests,
                'review_count' => $reviewCount,
            ]);
        }catch (\Exception $exception){
            LogHelper::error(
                message: "Lỗi DashboardService@getGeneralUserStats",
                ex: $exception,
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Lấy biểu đồ thống kê tiền (dashboard admin)
     * @param DateRangeDashboard $dateRange
     * @return ServiceReturn
     */
    public function getTransactionChartData(DateRangeDashboard $dateRange): ServiceReturn
    {
        try {
            $range = $dateRange->getDateRange();
            $config = $dateRange->getGroupingConfig();
            $start = $range['from'];
            $end = $range['to'];

            $period = \Carbon\CarbonPeriod::between($start, $end)->interval("1 " . $config['unit']);
            $labels = [];
            $placeholderIncome = [];

            foreach ($period as $date) {
                // Tạo Key để khớp với kết quả từ Database (PostgreSQL TO_CHAR)
                $key = match ($config['unit']) {
                    'hour'  => $date->format('H:00'),
                    'week'  => $date->startOfWeek()->format('Y-m-d'), // Postgres week đưa về thứ 2
                    'month' => $date->format('Y-m'),
                    'year'  => $date->format('Y'),
                    default => $date->format('Y-m-d'),
                };

                // Nếu đã tồn tại key (trường hợp week hoặc month trùng lặp trong chu kỳ) thì bỏ qua
                if (!isset($placeholderIncome[$key])) {
                    $placeholderIncome[$key] = 0;
                    $labels[$key] = $date->format($config['format']);
                }
            }

            $incomeRaw = $this->walletTransactionRepository->query()
                ->selectRaw("
                    TO_CHAR(DATE_TRUNC(?, created_at), ?) as date_key,
                    SUM(point_amount) as total
                ", [$config['unit'], $config['pg_format']])
                ->whereIn('type', WalletTransactionType::revenueStatus())
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date_key')
                ->pluck('total', 'date_key')
                ->toArray();

            // 3. Hợp nhất dữ liệu
            $incomeData = array_replace($placeholderIncome, $incomeRaw);

            return ServiceReturn::success([
                'labels' => array_values($labels),
                'income' => array_values($incomeData),
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Lấy thông tin dashboard của KTV (ở phía Khách hàng)
     * @return ServiceReturn
     */
    public function dashboardKtv($userId)
    {
        try {
            // 1. Chuẩn bị mốc thời gian (Dùng Carbon để chính xác và tận dụng Index DB tốt hơn)
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();
            $yesterdayStart = Carbon::yesterday()->startOfDay();

            // 2. Lấy Wallet (Check exists nhanh hơn nếu chỉ cần check, nhưng ở đây cần ID nên giữ nguyên)
            $wallet = $this->walletRepository->query()
                ->where('user_id', $userId)
                ->select('id')
                ->first();

            if (!$wallet) {
                throw new ServiceException(__('error.wallet_not_found'));
            }

            // Doanh thu hôm nay & hôm qua
            $revenueStats = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                ->where('created_at', '>=', $todayStart)
                ->toBase()
                ->selectRaw("SUM(CASE WHEN created_at >= ? THEN point_amount ELSE 0 END) as today", [$todayStart])
                ->selectRaw("SUM(CASE WHEN created_at < ? THEN point_amount ELSE 0 END) as yesterday", [$yesterdayStart])
                ->first();

            //  Gộp thống kê Booking (Completed & Pending)
            $bookingStats = $this->bookingRepository->query()
                ->where('ktv_user_id', $userId)
                ->whereBetween('booking_time', [$todayStart, $todayEnd]) // Tận dụng Index tốt hơn whereDate
                ->toBase()
                ->selectRaw("COUNT(CASE WHEN status = ? THEN 1 END) as completed", [BookingStatus::COMPLETED->value])
                ->selectRaw("COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending", [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
                ->first();

            //  Lấy booking sắp tới (hoặc mới nhất)
            $booking = $this->bookingRepository->query()
                ->where('ktv_user_id', $userId)
                ->whereIn('status', [
                    BookingStatus::CONFIRMED->value
                ])
                ->where('booking_time', '>=', $todayStart)
                ->where('booking_time', '<=', $todayEnd)
                ->orderBy('booking_time', 'asc')
                ->first();

            //Lấy booking đang diễn ra
            $bookingOnGoing = $this->bookingRepository->query()
                ->where('ktv_user_id', $userId)
                ->where('status', BookingStatus::ONGOING->value)
                ->first();


            // 7. Review mới nhất hôm nay
            $reviewToday = $this->reviewRepository->query()
                ->with('reviewer')
                ->where('user_id', $userId)
                ->whereBetween('review_at', [$todayStart, $todayEnd])
                ->orderBy('review_at', 'desc')
                ->get();

            return ServiceReturn::success(
                data: [
                    'booking' => $booking,
                    'booking_ongoing' => $bookingOnGoing,
                    'total_revenue_today' => (float)($revenueStats->today ?? 0),
                    'total_revenue_yesterday' => (float)($revenueStats->yesterday ?? 0),
                    'total_booking_completed_today' => (int)($bookingStats->completed ?? 0),
                    'total_booking_pending_today' => (int)($bookingStats->pending ?? 0),
                    'review_today' => $reviewToday,
                ]
            );
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error("Lỗi UserService@dashboardKtv", $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy thống kê tổng quan cho KTV (view ktv admin)
     * @param $userId
     * @return ServiceReturn
     */
    public function getGeneralKtvDashboard($userId): ServiceReturn
    {
        try {
            $dateRange = DateRangeDashboard::ALL->getDateRange();
            $fromDate = $dateRange['from'];
            $toDate = $dateRange['to'];

            if (!$userId) {
               throw new ServiceException(__('common_error.invalid_parameter'));
            }

            $user = $this->userRepository->queryUser()
                ->where('id', $userId)
                ->where('role', UserRole::KTV->value)
                ->first();
            if (!$user) {
                throw new ServiceException(__('error.user_not_found'));
            }
            $wallet = $user->wallet;
            if (!$wallet) {
                throw new ServiceException(__("error.wallet_not_found"));
            };


            // Doanh thu thực nhận
            $receivedIncome = $this->walletTransactionRepository->sumRealIncomePaymentBooking(
                ktvUserId: $user->id,
                from: $fromDate,
                to: $toDate,
            );


            // Số khách hàng đã đặt dịch vụ của KTV này
            $totalCustomers = $this->bookingRepository->getKtvTotalCustomerBooking(
                ktvId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // Số lượng đơn đã đặt lịch KTV trong khoảng thời gian
            $totalBookings = $this->bookingRepository->getKtvTotalBooking(
                ktvId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // Thu nhập Affiliate
            $affiliateIncome = $this->walletTransactionRepository->sumAffiliateProfit(
                walletId: $wallet->id,
                from: $fromDate,
                to: $toDate,
            );

            // Lượt review
            $totalReviews = $this->reviewRepository->countReviewByUser(
                userId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            return ServiceReturn::success([
                'received_income' => $receivedIncome,
                'total_customers' => $totalCustomers,
                'total_bookings' => $totalBookings,
                'affiliate_income' => $affiliateIncome,
                'total_reviews' => $totalReviews,
            ]);

        }catch (\Exception $exception){
            LogHelper::error(
                message: "Lỗi DashboardService@getGeneralKtvDashboard",
                ex: $exception,
            );
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Lấy dữ liệu dashboard tổng quan cho Agency
     * @param $userId
     * @param DateRangeDashboard $range
     * @return ServiceReturn
     */
    public function getAgencyDashboardData($userId, DateRangeDashboard $range): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị
            $dateRange = $range->getDateRange();

            // Lấy thông tin user
            $user = $this->userRepository->queryUser()
                ->where('role', UserRole::AGENCY->value)
                ->where('id', $userId)
                ->first();
            if (!$user) {
                throw new ServiceException(__('error.user_not_found'));
            }
            // Lấy thông tin wallet
            $walletData = $this->walletRepository->queryWallet()
                ->where('user_id', $userId)
                ->first();
            if (!$walletData) {
                throw new ServiceException(__('error.wallet_not_found'));
            }

            // Tổng lợi nhuận của các KTV mà mình giới thiệu trong khoảng thời gian
            $totalProfitReferralKtv = $this->walletTransactionRepository->sumReferralKtvProfit(
                walletId: $walletData->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
            );

            // Tổng lợi nhuận của mời Khách hàng (Affiliate) trong khoảng thời gian
            $totalProfitAffiliate = $this->walletTransactionRepository->sumAffiliateProfit(
                walletId: $walletData->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
            );

            // Số lượng Khách hàng đã giới thiệu trong khoảng thời gian
            $totalReferralCustomer = $this->userRepository->countReferralCustomers(
                referrerId: $user->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
            );

            // Tổng số lượng khách hàng đã đặt trong khoảng thời gian mà KTV này quản lý
            $totalCustomerOrderKtv = $this->bookingRepository->countManagedKtvCustomerBookingTime(
                leadUserId: $user->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
            );

            // Tổng số khách hàng đã đặt trong khoảng thời gian mà Agency này giới thiệu
            $totalCustomerAffiliateOrder = $this->bookingRepository->countReferredCustomerBookingTime(
                referrerId: $user->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
            );

            return ServiceReturn::success([
                'total_profit_referral_ktv' => $totalProfitReferralKtv, // Tổng chiết khấu lợi nhuận của mời KTV trong khoảng thời gian
                'total_profit_affiliate' => $totalProfitAffiliate, // Tổng chiết khấu lợi nhuận Affiliate trong khoảng thời gian
                'total_referral_customer' => $totalReferralCustomer, // Số lượng Khách hàng đã giới thiệu trong khoảng thời gian
                'total_customer_order_ktv' => $totalCustomerOrderKtv, // Tổng số lượng đơn đặt hàng mà user đang quản lý KTV trong khoảng thời gian
                'total_customer_affiliate_order' => $totalCustomerAffiliateOrder, // Tổng số khách hàng Affiliate đã đặt trong khoảng thời gian
            ]);
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi DashboardService@getGeneralDashboardData",
                ex: $e,
            );
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Lấy dữ liệu dashboard tổng quan cho KTV
     * @param $userId
     * @param DateRangeDashboard $range
     * @param int $page
     * @param int $limit
     * @return ServiceReturn
     */
    public function getListKtvPerformancePaginated($userId, DateRangeDashboard $range, int $page, int $limit): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị
            $dateRange = $range->getDateRange();
            // Lấy thông tin user
            $user = $this->userRepository->queryUser()
                ->whereIn('role', [UserRole::KTV->value, UserRole::AGENCY->value])
                ->where('id', $userId)
                ->first();
            if (!$user) {
                throw new ServiceException(__('error.user_not_found'));
            }
            $listKtvPerformance = $this->userRepository->getKtvPerformancePaginated(
                leadUserId: $user->id,
                from: $dateRange['from'],
                to: $dateRange['to'],
                page: $page,
                perPage: $limit,
            );
            return ServiceReturn::success($listKtvPerformance);
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi DashboardService@getListKtvPerformancePaginated",
                ex: $e,
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy tổng thu nhập trong khoảng thời gian
     * @param User $user
     * @param DateRangeDashboard $range
     * @return ServiceReturn
     */
    public function getKtvDashboardData(User $user, DateRangeDashboard $range): ServiceReturn
    {
        return $this->execute(function () use ($user, $range) {
            $uniqueKey = $range->value . '_' . $user->id;
            $cachedData = Caching::getCache(CacheKey::CACHE_KEY_TOTAL_INCOME, $uniqueKey);

            //Kiểm tra cache theo type
            if ($cachedData) {
                return ServiceReturn::success(data: $cachedData);
            }

            $dateRange = $range->getDateRange();
            $fromDate = $dateRange['from'];
            $toDate = $dateRange['to'];

            $wallet = $user->wallet;
            if (!$wallet) {
                throw new ServiceException(__("error.wallet_not_found"));
            };
            // 1. Tổng thu nhập (Kỳ này)
            $totalIncome = $this->walletTransactionRepository->sumRealIncomePaymentBooking(
                ktvUserId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // 2. Tổng thu nhập tiền di chuyển
            $transportationIncome = $this->walletTransactionRepository->sumRealIncomeTransportationBooking(
                ktvUserId: $user->id,
                from: $fromDate,
                to: $toDate,
            );


            // 3. Số khách hàng đã đặt trong khoảng thời gian
            $totalCustomers = $this->bookingRepository->getKtvTotalCustomerBooking(
                ktvId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // 4. Thu nhập Affiliate
            $affiliateIncome = $this->walletTransactionRepository->sumAffiliateProfit(
                walletId: $wallet->id,
                from: $fromDate,
                to: $toDate,
            );

            // 5. Lượt review
            $totalReviews = $this->reviewRepository->countReviewByUser(
                userId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // 6. Biểu đồ tổng thu nhập theo ngày
            $chartData = $this->walletTransactionRepository->getChartSumIncomePaymentBooking(
                ktvUserId: $user->id,
                range: $range,
            );

            $resultData = [
                'total_income' => (float)$totalIncome,
                'transportation_income' => (float)$transportationIncome,
                'total_customers' => (int)$totalCustomers,
                'affiliate_income' => (float)$affiliateIncome,
                'total_reviews' => (int)$totalReviews,
                'chart_data' => $chartData,
                'type_label' => $range->value
            ];

            Caching::setCache(
                key: CacheKey::CACHE_KEY_TOTAL_INCOME,
                value: $resultData,
                uniqueKey: $uniqueKey,
                expire: 5,
            );
            return $resultData;
        });
    }

    /**
     * Lấy số lượng yêu cầu trợ giúp khẩn cấp
     */
    public function getDangerSupportStats(): ServiceReturn
    {
        try {
            $pendingCount = $this->dangerSupportRepository->query()->where('status', DangerSupportStatus::PENDING->value)->count();
            return ServiceReturn::success(data:[
                'pending_danger_supports' => $pendingCount,
            ]);
        }
        catch (\Exception $exception) {
            LogHelper::error("Lỗi DashboardService@getDangerSupportStats", $exception);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

}
