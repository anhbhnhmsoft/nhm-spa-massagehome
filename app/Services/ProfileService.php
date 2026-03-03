<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Resources\Auth\UserResource;
use App\Repositories\BookingRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileService extends BaseService
{
    public function __construct(
        protected WalletRepository     $walletRepository,
        protected BookingRepository    $bookingRepository,
        protected CouponUserRepository $couponUserRepository,
    )
    {
        parent::__construct();
    }


    /**
     * Lấy thông tin dashboard profile của customer hiện tại
     * @return ServiceReturn
     */
    public function dashboardProfile(): ServiceReturn
    {
        return $this->execute(
            callback: function () {
                $user = Auth::user();
                // Số dư wallet
                $wallet = $this->walletRepository->query()
                    ->where('user_id', $user->id)
                    ->first();
                $walletBalance = $wallet?->balance ?? "0";
                // Lấy số lượng đặt lịch
                $bookingCount = $this->bookingRepository->getBookingDashboardCustomer($user->id);
                // Số lượng mã giảm giá chưa được sử dụng
                $couponUserCount = $this->couponUserRepository->countCouponUserNotUsedByUserId($user->id);
                return [
                    'wallet_balance' => $walletBalance,
                    'booking_count' => $bookingCount,
                    'coupon_user_count' => $couponUserCount,
                ];
            }
        );
    }
}
