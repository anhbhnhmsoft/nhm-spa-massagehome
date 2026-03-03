<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\callback;

class CouponService extends BaseService
{

    public function __construct(
        protected CouponRepository $couponRepository,
        protected CouponUsedRepository $couponUsedRepository,
        protected UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * Tăng số lần thu thập Coupon
     * @param int $couponId
     * @return ServiceReturn
     */
    public function incrementCollectCount(int $couponId): ServiceReturn
    {
        try {
            $coupon = $this->couponRepository->queryCoupon()
                ->where('id', $couponId)
                ->lockForUpdate()
                ->first();
            if (!$coupon) {
                throw new ServiceException(__("booking.coupon.not_found"));
            }
            $coupon->increment('count_collect');
            return ServiceReturn::success();
        } catch (ServiceException $e) {
            LogHelper::error('ConfigService@incrementCollectCount' . $e->getMessage());
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Tăng số lần sử dụng Coupon
     * @param int $couponId
     * @return ServiceReturn
     */
    public function incrementUsedCount(int $couponId): ServiceReturn
    {
        try {
            $coupon = $this->couponRepository->queryCoupon()
                ->where('id', $couponId)
                ->lockForUpdate()
                ->first();
            if (!$coupon) {
                throw new ServiceException(__("booking.coupon.not_found"));
            }

            // Kiểm tra giới hạn sử dụng trước khi increment
            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                throw new ServiceException(__("error.coupon_usage_limit_reached"));
            }

            $coupon->update([
                'used_count' => $coupon->used_count + 1,
            ]);
            return ServiceReturn::success();
        } catch (ServiceException $e) {
            LogHelper::error('ConfigService@incrementUsedCount' . $e->getMessage());
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Kiểm tra, sử dụng, tăng used_count cho Coupon
     * @param string $couponId
     * @param string $userId
     * @param string $serviceId
     * @param string $bookingId
     * @return ServiceReturn
     */
    public function useCoupon(
        string $couponId,
        string $userId,
        string $serviceId,
        string $bookingId,
    ): ServiceReturn {
        // Sử dụng transaction để đảm bảo tính atomic
        return DB::transaction(function () use ($couponId, $userId, $serviceId, $bookingId) {
            try {
                // 1. Khóa dòng Coupon để đảm bảo tính Atomic cho used_count và config JSON
                $coupon = $this->couponRepository->query()
                    ->where('id', $couponId)
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw new ServiceException(__("booking.coupon.not_found"));
                }
                LogHelper::debug('CouponService@useCoupon: ' . json_encode($coupon));
                // Kiểm tra usage_limit TRƯỚC KHI thực hiện bất kỳ thao tác nào
                if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                    throw new ServiceException(__("booking.coupon.usage_limit_reached"));
                }

                // Kiểm tra coupon còn hiệu lực không
                $now = Carbon::now();
                if (!$coupon->is_active) {
                    throw new ServiceException(__("booking.coupon.not_active"));
                }
                if ($now->isBefore(Carbon::parse($coupon->start_at))) {
                    throw new ServiceException(__("booking.coupon.not_yet_started"));
                }
                if ($now->isAfter(Carbon::parse($coupon->end_at))) {
                    throw new ServiceException(__("booking.coupon.expired"));
                }

                $user = $this->userRepository->find($userId);
                if (!$user) {
                    throw new ServiceException(__("user.not_found"));
                }

                // 2. Kiểm tra sở hữu trong ví (coupon_users)
                $userPivot = $user->collectionCoupons()
                    ->where('coupon_id', $couponId)
                    ->lockForUpdate()
                    ->first();

                // Nếu đã có trong ví và ĐÃ DÙNG rồi thì báo lỗi
                if ($userPivot && $userPivot->pivot->is_used) {
                    throw new ServiceException(__("booking.coupon.used"));
                }

                // 3. Thực hiện GHI

                // Trường hợp TH1: CHƯA sở hữu (Khách dùng trực tiếp từ coupon gợi ý)
                if (!$userPivot) {
                    // Tăng số lượng thu thập trong ngày (vì họ vừa dùng vừa "nhặt")
                    $collectResult = $this->incrementCollectCount($coupon->id);
                    if ($collectResult->isError()) {
                        throw new ServiceException($collectResult->getMessage() ?? __("booking.coupon.collect_limit_reached"));
                    }

                    // Thêm vào ví và đánh dấu đã dùng
                    $user->collectionCoupons()->syncWithoutDetaching([
                        $coupon->id => ['is_used' => true]
                    ]);
                }
                // Trường hợp TH2: ĐÃ sở hữu (Đã nhặt vào ví trước đó)
                else {
                    // Cập nhật trạng thái trong ví thành đã dùng
                    $user->collectionCoupons()->updateExistingPivot($coupon->id, ['is_used' => true]);
                }

                // 4. Tăng lượt sử dụng thực tế (used_count)
                $successIncrement = $this->incrementUsedCount($coupon->id);

                if ($successIncrement->isError()) {
                    throw new ServiceException($successIncrement->getMessage() ?? __("error.coupon_usage_limit_reached_or_daily_full"));
                }

                // 5. Ghi lịch sử giao dịch (Bảng coupon_used)
                $this->couponUsedRepository->create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'booking_id' => $bookingId,
                ]);

                return ServiceReturn::success();
            } catch (ServiceException $e) {
                LogHelper::error('CouponService@useCoupon: ' . $e->getMessage());
                return ServiceReturn::error(
                    message: $e->getMessage()
                );
            } catch (\Exception $e) {
                LogHelper::error('CouponService@useCoupon: ' . $e->getMessage());
                return ServiceReturn::error(
                    message: __("common_error.server_error")
                );
            }
        });
    }
}
