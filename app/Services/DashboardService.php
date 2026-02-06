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
    public function getGeneralStats(): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = DateRangeDashboard::ALL->getDateRange();
            $start = $dateRange['from'];
            $end = $dateRange['to'];

            $stats = $this->walletTransactionRepository->getFinancialDashboardStats($start, $end);

            // Lấy tổng số tiền nạp vào (Deposit)
            $totalIncome   = (float) $stats->total_income;
            // Tổng chi phí (Operation Cost)
            $operationCost = (float) $stats->operation_cost;
            // chi phí dành cho đại lý
            $agencyCost    = (float) $stats->agency_cost;
            // chi phí dành cho KTV
            $ktvCost       = (float) $stats->ktv_cost;
            // chi phí dành cho Affiliate
            $affiliateCost = (float) $stats->affiliate_cost;
            // Tính lợi nhuận
            $profit = round($totalIncome - $operationCost, 2);

            return ServiceReturn::success([
                'total_income' => $totalIncome,
                'operation_cost' => $operationCost,
                'agency_cost' => $agencyCost,
                'ktv_cost' => $ktvCost,
                'affiliate_cost' => $affiliateCost,
                'profit' => $profit,
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
    public function getGeneralBookingStats(): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = DateRangeDashboard::ALL->getDateRange();
            $start = $dateRange['from'];
            $end = $dateRange['to'];

            $stats = $this->bookingRepository->getBookingStats($start, $end);

            // Tổng số đơn hàng
            $totalBooking     = $stats->total ?? 0;
            // Số đơn hàng đang chờ
            $pendingBooking   = $stats->pending ?? 0;
            // Số đơn hàng đang tiến hành
            $ongoingBooking   = $stats->ongoing ?? 0;
            // Số đơn hàng đã hoàn thành
            $completedBooking = $stats->completed ?? 0;
            // Số đơn hàng đã hủy và hoàn tiền
            $canceledBooking  = $stats->canceled ?? 0;

            return ServiceReturn::success([
                'total_booking' => $totalBooking,
                'pending_booking' => $pendingBooking,
                'ongoing_booking' => $ongoingBooking,
                'completed_booking' => $completedBooking,
                'canceled_booking' => $canceledBooking,
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
    public function getGeneralUserStats(): ServiceReturn
    {
        try {
            // Lấy khoảng thời gian hiển thị (Là toàn bộ thời gian)
            $dateRange = DateRangeDashboard::ALL->getDateRange();
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
                ->whereIn('type', WalletTransactionType::incomeStatus())
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


            // Tổng thu nhập (Kỳ này)
            $totalIncome = $this->bookingRepository->getKtvTotalIncome(
                ktvId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

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
                'total_income' => $totalIncome,
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
        try {
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
            $totalIncome = $this->bookingRepository->getKtvTotalIncome(
                ktvId: $user->id,
                from: $fromDate,
                to: $toDate,
            );

            // 2. Doanh thu thực nhận
            $receivedIncome = $this->walletTransactionRepository->sumRealIncomePaymentBooking(
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

            // 6. Dữ liệu biểu đồ
            if ($range === DateRangeDashboard::DAY) {
                $chartData = $this->bookingRepository->query()
                    ->selectRaw("
                        CASE
                            WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 6 THEN '00:00-06:00'
                            WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 12 THEN '06:00-12:00'
                            WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 18 THEN '12:00-18:00'
                            ELSE '18:00-00:00'
                        END as date,
                        SUM(price) as total
                    ")
                    ->where('ktv_user_id', $user->id)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->whereBetween('booking_time', [$fromDate, $toDate])
                    ->groupByRaw("date")
                    ->orderByRaw("MIN(booking_time) ASC")
                    ->get();
            }
            else {
                // Điều chỉnh logic format ngày cho year, month, week
                $format = match ($range) {
                    DateRangeDashboard::YEAR => "to_char(booking_time, 'YYYY-MM')", // Lấy theo tháng nếu là Year
                    default => "to_char(booking_time, 'YYYY-MM-DD')",
                };

                $chartData = $this->bookingRepository->query()
                    ->selectRaw("$format as date, SUM(price) as total")
                    ->where('ktv_user_id', $user->id)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->whereBetween('booking_time', [$fromDate, $toDate])
                    ->groupByRaw("date")
                    ->orderBy('date', 'asc')
                    ->get();
            }

            $resultData = [
                'total_income' => (float)$totalIncome,
                'received_income' => (float)$receivedIncome,
                'total_customers' => $totalCustomers,
                'affiliate_income' => (float)$affiliateIncome,
                'total_reviews' => $totalReviews,
                'chart_data' => $chartData,
                'type_label' => $range->value
            ];

            Caching::setCache(
                key: CacheKey::CACHE_KEY_TOTAL_INCOME,
                value: $resultData,
                uniqueKey: $uniqueKey,
                expire: 5,
            );
            return ServiceReturn::success(data: $resultData);
        }
        catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        }
        catch (\Exception $exception) {
            LogHelper::error("Lỗi BookingService@totalIncome", $exception);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
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
