<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ReviewApplicationStatus;
use App\Enums\WalletTransactionType;
use App\Enums\WalletTransactionStatus;
//        use App\Repositories\AffiliateEarningRepository;
use App\Repositories\BookingRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletTransactionRepository;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    public function __construct(
        protected BookingRepository $bookingRepository,
        protected UserRepository $userRepository,
        protected UserReviewApplicationRepository $userReviewApplicationRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected ReviewRepository $reviewRepository,
//        protected AffiliateEarningRepository $affiliateEarningRepository,
    ) {
        parent::__construct();
    }
    /**
     * Lấy thống kê tổng quan dashboard
     * @return ServiceReturn
     */
    public function getDashboardStats(): ServiceReturn
    {
        try {
            // Revenue (Points) - Deposit only
            $revenue = $this->walletTransactionRepository->query()
                ->whereIn('type', [
                    WalletTransactionType::DEPOSIT_QR_CODE,
                    WalletTransactionType::DEPOSIT_ZALO_PAY,
                    WalletTransactionType::DEPOSIT_MOMO_PAY
                ])
                ->where('status', WalletTransactionStatus::COMPLETED)
                ->sum('money_amount');

            // Booking Value - Completed bookings
            $bookingValue = $this->bookingRepository->query()
                ->where('status', BookingStatus::COMPLETED)
                ->sum('price');

            $newBookings = $this->bookingRepository->query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            // New Users (This Month)
            $newUsers = $this->userRepository->query()
                ->where('created_at', '>=', now()->startOfMonth())
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
     * Lấy dữ liệu revenue trend theo tháng hiện tại
     * @return ServiceReturn
     */
    public function getRevenueChart(): ServiceReturn
    {
        try {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
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
     * @return ServiceReturn
     */
    public function getBookingStatusChart(): ServiceReturn
    {
        try {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
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
     * @return ServiceReturn
     */
    public function getReviewRatingChart(): ServiceReturn
    {
        try {
            $data = $this->reviewRepository->query()
                ->select('rating', DB::raw('count(*) as count'))
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
     * @return ServiceReturn
     */
    public function getTopServicesChart(): ServiceReturn
    {
        try {
            $data = $this->bookingRepository->query()
                ->select('service_id', DB::raw('count(*) as count'))
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
     * @return ServiceReturn
     */
    public function getUserActivityChart(): ServiceReturn
    {
        try {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
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
     */
    public function getOperationCostStats(): ServiceReturn
    {
        try {
            // 1. Operation Costs
            $operationCost = $this->walletTransactionRepository->query()
                ->whereIn('type', [
                    WalletTransactionType::WITHDRAWAL,
                    WalletTransactionType::PAYMENT_FOR_KTV,
                    WalletTransactionType::AFFILIATE
                ])->sum('money_amount');

            // 2. Primary Order Count
            $primaryOrderCount = $this->bookingRepository->query()->count();

            // 3. Primary Service Value
            $primaryServiceValue = $this->bookingRepository->query()->sum('price');

            // 4. Canceled Orders
            $canceledOrders = $this->bookingRepository->query()
                ->where('status', BookingStatus::CANCELED)
                ->count();

            // 5. Refund Amount
            $refundAmount = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::REFUND)
                ->sum('money_amount');

            return ServiceReturn::success([
                'operation_cost' => $operationCost,
                'primary_order_count' => $primaryOrderCount,
                'primary_service_value' => $primaryServiceValue,
                'canceled_orders' => $canceledOrders,
                'refund_amount' => $refundAmount,
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get General Stats
     */
    public function getGeneralStats(): ServiceReturn
    {
        try {
            // 1. Order Volume
            $orderVolume = $this->bookingRepository->query()->count();

            // 2. Sales
            $sales = $this->bookingRepository->query()->sum('price');

            // 3. Commission
            $commissionAmount = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::AFFILIATE)
                ->sum('money_amount');

            // 4. Net Sales
            $refunds = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::REFUND)
                ->sum('money_amount');
            $netSales = $sales - $refunds;

            // 5. Coupon
            $couponAmount = $this->bookingRepository->query()->sum('price_before_discount')
                - $this->bookingRepository->query()->sum('price');
            $couponAmount = max($couponAmount, 0);

            return ServiceReturn::success([
                'order_volume' => $orderVolume,
                'sales' => $sales,
                'net_sales' => $netSales,
                'commission_amount' => $commissionAmount,
                'coupon_amount' => $couponAmount,
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Get Technician Status Stats
     */
    public function getTechnicianStatusStats(): ServiceReturn
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
     */
    public function getRevenueRefundChartData(): ServiceReturn
    {
        try {
            $start = now()->startOfYear();
            $end = now()->endOfYear();
            $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m')] = 0;
            }

            // Revenue
            $revenueData = $this->bookingRepository->query()
                ->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, SUM(price) as total')
                ->where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            // Refund
            $refundData = $this->walletTransactionRepository->query()
                ->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, SUM(money_amount) as total')
                ->where('type', WalletTransactionType::REFUND)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->pluck('total', 'month')
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
     */
    public function getProfitChartData(): ServiceReturn
    {
        try {
            $start = now()->startOfYear();
            $end = now()->endOfYear();
            $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

            $dates = [];
            foreach ($period as $date) {
                $dates[$date->format('Y-m')] = 0;
            }

            $data = $this->bookingRepository->query()
                ->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, SUM(price) as total')
                ->where('status', BookingStatus::COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            return ServiceReturn::success([
                'data' => array_values(array_merge($dates, $data)),
                'labels' => array_keys($dates),
            ]);
        } catch (\Exception $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }
}
