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
            $affiliateCommission = $this->affiliateEarningRepository->query()
                ->sum('commission_amount');

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
}
