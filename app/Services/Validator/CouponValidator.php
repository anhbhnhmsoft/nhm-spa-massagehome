<?php

namespace App\Services\Validator;

use App\Core\Service\ServiceException;
use App\Models\Coupon;
use App\Models\User;
use App\Repositories\CouponRepository;
use Illuminate\Support\Carbon;

class CouponValidator
{

    public function __construct(
        protected CouponRepository $couponRepository,
    )
    {

    }

    /**
     * Phương thức kiểm tra tính hợp lệ của Coupon khi sử dụng.
     * @param Coupon $coupon
     * @param User $user
     * @return void
     * @throws ServiceException
     */
    public function validateUseCoupon(Coupon $coupon, User $user)
    {

        // Kiểm tra trạng thái
        if (!$coupon->is_active) {
            throw new ServiceException(
                message: __("booking.coupon.not_active")
            );
        }

        // Kiểm tra thời gian
        $now = Carbon::now();
        if ($now->isBefore(Carbon::parse($coupon->start_at))) {
            throw new ServiceException(
                message: __("booking.coupon.not_yet_started")
            );
        }
        if ($now->isAfter(Carbon::parse($coupon->end_at))) {
            throw new ServiceException(
                message: __("error.coupon_expired")
            );
        }

        //Kiểm tra giới hạn sử dụng
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new ServiceException(
                message: __("booking.coupon.usage_limit_reached")
            );
        }

        //  Kiểm tra sở hữu trong ví (coupon_users)
        $userPivot = $user->collectionCoupons()
            ->where('coupon_id', $coupon->id)
            ->first();

        // Nếu đã có trong ví và ĐÃ DÙNG rồi thì báo lỗi
        if ($userPivot && $userPivot->pivot->is_used) {
            throw new ServiceException(__("booking.coupon.used"));
        }
    }
}
