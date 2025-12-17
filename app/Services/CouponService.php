<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Models\Coupon;
use App\Models\User;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService extends BaseService
{

    public function __construct(
        protected CouponRepository $couponRepository,
        protected CouponUsedRepository $couponUsedRepository,
    ) {
        parent::__construct();
    }

    /**
     * Phương thức kiểm tra tính hợp lệ của Coupon.
     *
     * @param string $couponId
     * @param string $userId
     * @param string $serviceId
     * @param float $priceBeforeDiscount
     * @return ServiceReturn
     */
    public function validateCoupon(string $couponId, string $userId, string $serviceId, float $priceBeforeDiscount): ServiceReturn
    {

        $coupon = $this->couponRepository->query()->find($couponId);

        if (!$coupon) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_found")
            );
        }


        // --- 1. Kiểm tra trạng thái và thời gian (dùng dữ liệu từ Cache) ---
        if (!$coupon->is_active) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_active")
            );
        }

        $now = Carbon::now();
        if ($now->isBefore(Carbon::parse($coupon->start_at))) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_yet_started")
            );
        }
        if ($now->isAfter(Carbon::parse($coupon->end_at))) {
            return ServiceReturn::error(
                message: __("booking.coupon.expired")
            );
        }

        // --- 2. Kiểm tra điều kiện áp dụng ---
        if ($coupon->for_service_id !== null && $coupon->for_service_id != $serviceId) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_match_service")
            );
        }

        // --- 3. Kiểm tra giới hạn sử dụng ---
        // Kiểm tra giới hạn sử dụng toàn bộ
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return ServiceReturn::error(
                message: __("booking.coupon.usage_limit_reached")
            );
        }


        // --- 4. Kiểm tra giá trị tối đa của mã có vượt quá giá trị sau áp dụng không ---

        if ($coupon->is_percentage) {
            $discountAmount = ($priceBeforeDiscount * $coupon->discount_value) / 100;
        } else {
            $discountAmount = $priceBeforeDiscount - $coupon->discount_value;
        }

        if ($discountAmount > $coupon->max_discount) {
            return ServiceReturn::error(
                message: __("booking.coupon.max_discount_exceeded")
            );
        }

        // -- 5. Kiểm tra thời gian sử dụng hợp lệ hay không --
        $config = $coupon->config ?? [];
        $allowTime = $config['allowed_time_slots'] ?? [];

        if (!empty($allowTime)) {
            $currentTime = Carbon::now()->format('H:i');
            $isValidTimeSlot = false;

            foreach ($allowTime as $time) {
                // Chỉ cần khớp 1 trong các khung giờ là OK
                if ($currentTime >= $time['start'] && $currentTime <= $time['end']) {
                    $isValidTimeSlot = true;
                    break;
                }
            }

            if (!$isValidTimeSlot) {
                return ServiceReturn::error(
                    message: __('booking.coupon.not_allowed_time')
                );
            }
        }

        // Nếu mọi thứ hợp lệ, trả về dữ liệu
        return ServiceReturn::success(
            data: [
                'coupon' => $coupon,
                'discount_amount' => $discountAmount,
            ]
        );
    }

    /**
     * Phương thức GHI (Write) - Kiểm tra, sử dụng, tăng used_count và đồng bộ Cache.
     *
     * @param string $couponId
     * @param string $userId
     * @param string $serviceId
     * @param string $bookingId
     * @param float $discountApplied Giá trị giảm giá đã được tính toán ở bước kiểm tra
     * @return ServiceReturn
     */
    public function useCoupon(
        string $couponId,
        string $userId,
        string $serviceId,
        string $bookingId,
    ): ServiceReturn {
        DB::beginTransaction();
        try {
        // 1. Khóa dòng Coupon để đảm bảo tính Atomic cho used_count và config JSON
            /** @var Coupon|null $coupon */
            $coupon = $this->couponRepository->query()
                ->where('id', $couponId)
                ->lockForUpdate()
                ->first();

            if (!$coupon) {
                throw new ServiceException(__("booking.coupon.not_found"));
            }

            /** @var User $user */
            $user = User::find($userId);

            // 2. Kiểm tra sở hữu trong ví (coupon_users)
            $userPivot = $user->collectionCoupons()
                ->where('coupon_id', $couponId)
                ->first();

            // Nếu đã có trong ví và ĐÃ DÙNG rồi thì báo lỗi
            if ($userPivot && $userPivot->pivot->is_used) {
                throw new ServiceException(__("booking.coupon.used"));
            }

            // 3. Thực hiện GHI

            // Trường hợp TH1: CHƯA sở hữu (Khách dùng trực tiếp từ coupon gợi ý)
            if (!$userPivot) {
                // Tăng số lượng thu thập trong ngày (vì họ vừa dùng vừa "nhặt")
                $this->couponRepository->incrementDailyCollectCountAtomic($coupon->id);

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

            // 4. Tăng lượt sử dụng thực tế (used_count + daily_used trong config)
            // Lưu ý: Hàm này trong Repository của bạn đã xử lý JSON history và check usage_limit
            $successIncrement = $this->couponRepository->incrementUsedCountAtomic($coupon->id);

            if (!$successIncrement) {
                throw new ServiceException(__("booking.coupon.usage_limit_reached_or_daily_full"));
            }

            // 5. Ghi lịch sử giao dịch (Bảng coupon_used)
            $this->couponUsedRepository->create([
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'service_id' => $serviceId,
                'booking_id' => $bookingId,
            ]);

            DB::commit();

            return ServiceReturn::success(
                message: __("booking.coupon.used_successfully")
            );
        } catch (ServiceException $e) {
            DB::rollBack();
            return ServiceReturn::error(message: $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error("Lỗi useCouponAndSyncCache", $e);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }
}
