<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\DateRangeDashboard;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Enums\WalletTransactionStatus;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    public function __construct(
        protected BookingRepository               $bookingRepository,
        protected UserRepository                  $userRepository,
        protected UserReviewApplicationRepository $userReviewApplicationRepository,
        protected WalletTransactionRepository     $walletTransactionRepository,
        protected ReviewRepository                $reviewRepository,
        protected WalletRepository                $walletRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy thống kê tổng quan dashboard
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

            // Revenue (Points) - Deposit only
            $revenue = $this->walletTransactionRepository->query()
                ->whereIn('type', [
                    WalletTransactionType::DEPOSIT_QR_CODE,
                    WalletTransactionType::DEPOSIT_ZALO_PAY,
                    WalletTransactionType::DEPOSIT_MOMO_PAY
                ])
                ->where('status', WalletTransactionStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->sum('money_amount');

            // Booking Value - Completed bookings
            $bookingValue = $this->bookingRepository->query()
                ->where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->sum('price');

            $newBookings = $this->bookingRepository->query()
                ->whereBetween('created_at', [$start, $end])
                ->count();

            // New Users
            $newUsers = $this->userRepository->query()
                ->whereBetween('created_at', [$start, $end])
                ->count();

            // Pending Profiles
            $pendingProfiles = $this->userReviewApplicationRepository->query()
                ->where('status', ReviewApplicationStatus::PENDING)
                ->count();

            // Affiliate Commission
            $affiliateCommission = 0;

            return ServiceReturn::success(
                data: [
                    'revenue' => $revenue,
                    'booking_value' => $bookingValue,
                    'new_bookings' => $newBookings,
                    'new_users' => $newUsers,
                    'pending_profiles' => $pendingProfiles,
                    'affiliate_commission' => $affiliateCommission,
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy dữ liệu revenue trend theo khoảng thời gian
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getRevenueChart(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
            $period = CarbonPeriod::create($start, $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m-d')] = 0;
            }

            // Deposit Revenue
            $depositsData = $this->walletTransactionRepository->query()
                ->selectRaw('DATE(created_at) as date, SUM(money_amount) as total')
                ->whereIn('type', [
                    WalletTransactionType::DEPOSIT_QR_CODE,
                    WalletTransactionType::DEPOSIT_ZALO_PAY,
                    WalletTransactionType::DEPOSIT_MOMO_PAY
                ])
                ->where('status', WalletTransactionStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            $deposits = array_merge($dates, $depositsData);

            // Booking Revenue
            $bookingsData = $this->bookingRepository->query()
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            $bookings = array_merge($dates, $bookingsData);

            return ServiceReturn::success(
                data: [
                    'deposits' => array_values($deposits),
                    'bookings' => array_values($bookings),
                    'labels' => array_keys($dates),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy dữ liệu booking status chart
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getBookingStatusChart(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
            $period = CarbonPeriod::create($start, $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m-d')] = 0;
            }

            $statuses = BookingStatus::cases();
            $datasets = [];

            $colors = [
                BookingStatus::PENDING->value => 'rgb(234, 179, 8)',
                BookingStatus::ONGOING->value => 'rgb(168, 85, 247)',
                BookingStatus::CONFIRMED->value => 'rgb(59, 130, 246)',
                BookingStatus::COMPLETED->value => 'rgb(34, 197, 94)',
                BookingStatus::CANCELED->value => 'rgb(239, 68, 68)',
            ];

            foreach ($statuses as $status) {
                $countData = $this->bookingRepository->query()
                    ->selectRaw('DATE(created_at) as date, count(*) as count')
                    ->where('status', $status)
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $filledData = array_merge($dates, $countData);

                $datasets[] = [
                    'label' => $status->label(),
                    'data' => array_values($filledData),
                    'backgroundColor' => $colors[$status->value] ?? '#cccccc',
                    'fill' => true,
                ];
            }

            return ServiceReturn::success(
                data: [
                    'datasets' => $datasets,
                    'labels' => array_keys($dates),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy dữ liệu review rating chart
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getReviewRatingChart(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

            $data = $this->reviewRepository->query()
                ->select('rating', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('rating')
                ->orderBy('rating')
                ->get();

            $ratings = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];

            foreach ($data as $item) {
                $ratings[$item->rating] = $item->count;
            }

            return ServiceReturn::success(
                data: [
                    'data' => array_values($ratings),
                    'labels' => array_keys($ratings),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy top services chart
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getTopServicesChart(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

            $data = $this->bookingRepository->query()
                ->select('service_id', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('service_id')
                ->orderByDesc('count')
                ->limit(10)
                ->with('service')
                ->get();

            return ServiceReturn::success(
                data: [
                    'counts' => $data->pluck('count'),
                    'labels' => $data->pluck('service.name'),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy user activity chart
     * @param string|null $startDate
     * @param string|null $endDate
     * @return ServiceReturn
     */
    public function getUserActivityChart(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
            $period = CarbonPeriod::create($start, $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m-d')] = 0;
            }

            // New Users
            $newUsersData = $this->userRepository->query()
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $newUsers = array_merge($dates, $newUsersData);

            // Active Users (based on last_login_at)
            $activeUsersData = $this->userRepository->query()
                ->selectRaw('DATE(last_login_at) as date, count(*) as count')
                ->whereNotNull('last_login_at')
                ->whereBetween('last_login_at', [$start, $end])
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $activeUsers = array_merge($dates, $activeUsersData);

            return ServiceReturn::success(
                data: [
                    'new_users' => array_values($newUsers),
                    'active_users' => array_values($activeUsers),
                    'labels' => array_keys($dates),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy user role chart
     * @return ServiceReturn
     */
    public function getUserRoleChart(): ServiceReturn
    {
        try {
            $data = $this->userRepository->query()
                ->select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get();

            return ServiceReturn::success(
                data: [
                    'counts' => $data->pluck('count'),
                    'roles' => $data->pluck('role'),
                ]
            );
        } catch (\Exception $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Get Operation Cost Stats
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getOperationCostStats(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

            $activeOrder = $this->bookingRepository->query()
                ->whereBetween('created_at', [$start, $end])
                ->where('status', BookingStatus::ONGOING->value)->count();
            $refundAmount = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::REFUND)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->sum('money_amount');
            $feeAmount = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::AFFILIATE->value)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->sum('money_amount');
            $depositAmount = $this->walletTransactionRepository->query()
                ->whereIn('type', [
                    WalletTransactionType::DEPOSIT_MOMO_PAY->value,
                    WalletTransactionType::DEPOSIT_QR_CODE->value,
                    WalletTransactionType::DEPOSIT_ZALO_PAY->value,
                    WalletTransactionType::DEPOSIT_WECHAT_PAY->value,
                ])
                ->whereBetween('created_at', [$start, $end])
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->sum('money_amount');
            $feeAmountForKtvForCustomer = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::REFERRAL_KTV->value)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->sum('money_amount');
            return ServiceReturn::success([
                'active_order_count' => $activeOrder,
                'refund_amount' => $refundAmount,
                'fee_amount_for_affiliate' => $feeAmount,
                'fee_amount_for_ktv_for_customer' => $feeAmountForKtvForCustomer,
                'deposit_amount' => $depositAmount,
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get General Stats
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getGeneralStats(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

            $totalBooking = $this->bookingRepository->query()
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $completedBooking = $this->bookingRepository->query()
                ->where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $canceledBooking = $this->bookingRepository->query()
                ->where('status', BookingStatus::CANCELED)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $grossRevenue = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->where('wallet_transactions.type', WalletTransactionType::PAYMENT)
                ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED)
                ->where('sb.status', '!=', BookingStatus::CANCELED)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->sum(DB::raw('ABS(wallet_transactions.money_amount)'));

            $netRevenue = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->whereIn('wallet_transactions.type', [WalletTransactionType::PAYMENT, WalletTransactionType::REFUND])
                ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED)
                ->where('sb.status', '!=', BookingStatus::CANCELED)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->sum('wallet_transactions.money_amount');

            $ktvCost = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->whereIn('wallet_transactions.type', [
                    WalletTransactionType::PAYMENT_FOR_KTV,
                    WalletTransactionType::RETRIEVE_PAYMENT_REFUND_KTV
                ])
                ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED)
                ->where('sb.status', '!=', BookingStatus::CANCELED)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->sum('wallet_transactions.money_amount');

            $ktvCostMagnitude = abs($ktvCost);

            $affiliateCost = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->where('wallet_transactions.type', WalletTransactionType::AFFILIATE)
                ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED)
                ->where('sb.status', '!=', BookingStatus::CANCELED)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->sum('wallet_transactions.money_amount');
            $paymentFailed = $this->bookingRepository->query()
                ->where('status', BookingStatus::PAYMENT_FAILED)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $bookingConfirm = $this->bookingRepository->query()
                ->where('status', BookingStatus::CONFIRMED)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $activeOrder = $this->bookingRepository->query()
                ->where('status', BookingStatus::ONGOING)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $affiliateCostMagnitude = abs($affiliateCost);

            $netProfit = $grossRevenue - $ktvCostMagnitude - $affiliateCostMagnitude;

            return ServiceReturn::success([
                'total_booking' => $totalBooking,
                'completed_booking' => $completedBooking,
                'canceled_booking' => $canceledBooking,
                'gross_revenue' => $grossRevenue,
                'net_revenue' => $netRevenue,
                'ktv_cost' => $ktvCostMagnitude,
                'net_profit' => $netProfit,
                'affiliate_cost' => $affiliateCostMagnitude,
                'payment_failed' => $paymentFailed,
                'booking_confirmed' => $bookingConfirm,
                'active_order_count' => $activeOrder,
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get Technician Status Stats
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getTechnicianStatusStats(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            // Total KTVs
            $totalKtv = \App\Models\User::where('role', \App\Enums\UserRole::KTV->value)->count();

            // Online KTVs
            // Ideally we should move this to repository too but sticking to service logic
            $allKtvs = \App\Models\User::where('role', \App\Enums\UserRole::KTV->value)->get();
            $onlineKtvCount = $allKtvs->filter(fn($user) => $user->is_online)->count();

            // Working KTVs
            $workingKtvIds = $this->bookingRepository->query()
                ->where('status', BookingStatus::ONGOING)
                ->distinct()
                ->pluck('ktv_user_id')
                ->toArray();
            $workingKtvCount = count($workingKtvIds);

            // Resting KTVs
            $restingKtvCount = max($onlineKtvCount - $workingKtvCount, 0);

            return ServiceReturn::success([
                'total_ktv' => $totalKtv,
                'online_ktv_count' => $onlineKtvCount,
                'working_ktv_count' => $workingKtvCount,
                'resting_ktv_count' => $restingKtvCount,
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get Revenue Refund Chart Data
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getRevenueRefundChartData(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
            $period = \Carbon\CarbonPeriod::create($start, $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m-d')] = 0;
            }

            /// Revenue: Gross Revenue logic (PAYMENT, !CANCELED)
            $revenueData = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->selectRaw('DATE(wallet_transactions.created_at) as date, SUM(ABS(wallet_transactions.money_amount)) as total')
                ->where('wallet_transactions.type', WalletTransactionType::PAYMENT->value)
                ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED->value)
                ->where('sb.status', '!=', BookingStatus::CANCELED->value)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            // Refund: REFUND, !CANCELED
            $refundData = $this->walletTransactionRepository->query()
                ->join('service_bookings as sb', 'sb.id', '=', 'wallet_transactions.foreign_key')
                ->selectRaw('DATE(wallet_transactions.created_at) as date, SUM(wallet_transactions.money_amount) as total')
                ->where('wallet_transactions.type', '=', WalletTransactionType::REFUND->value)
                ->where('wallet_transactions.status', '=', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('wallet_transactions.created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            return ServiceReturn::success([
                'revenue' => array_values(array_merge($dates, $revenueData)),
                'refunds' => array_values(array_merge($dates, $refundData)),
                'labels' => array_keys($dates),
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get Profit Chart Data
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getProfitChartData(?string $startDate = null, ?string $endDate = null): ServiceReturn
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
            $period = \Carbon\CarbonPeriod::create($start, $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m-d')] = 0;
            }

            $data = $this->bookingRepository->query()
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->where('status', '=', BookingStatus::COMPLETED->value)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            return ServiceReturn::success([
                'data' => array_values(array_merge($dates, $data)),
                'labels' => array_keys($dates),
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
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

            // Tổng lợi nhuận của mời Agency trong khoảng thời gian
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
            $totalIncome = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->where('status', BookingStatus::COMPLETED->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('price');

            // 2. Doanh thu thực nhận
            $incomeData = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw("
                    SUM(point_amount) FILTER (WHERE type = ?) as total_received,
                    SUM(point_amount) FILTER (WHERE type = ?) as total_retrieve
                ", [
                    WalletTransactionType::PAYMENT_FOR_KTV->value,
                    WalletTransactionType::RETRIEVE_PAYMENT_REFUND_KTV->value
                ])
                ->first();
            $receivedIncome = ($incomeData->total_received ?? 0) - ($incomeData->total_retrieve ?? 0);


            // 3. Số khách hàng đã đặt trong khoảng thời gian
            $totalCustomers = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->where('status', BookingStatus::COMPLETED->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->count();

            // 4. Thu nhập Affiliate
            $affiliateIncome = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::AFFILIATE->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('point_amount');

            // 5. Lượt review
            $totalReviews = $this->reviewRepository->query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->count();

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
            } else {
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
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $exception) {
            LogHelper::error("Lỗi BookingService@totalIncome", $exception);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }
}
