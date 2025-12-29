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
     * Phương thức kiểm tra tính hợp lệ của Coupon khi sử dụng.
     * @param int $couponId
     * @param int $serviceId
     * @param  $priceBeforeDiscount
     * @return ServiceReturn
     */
    public function validateUseCoupon(int $couponId, int $serviceId, $priceBeforeDiscount): ServiceReturn
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

        // --- 2. Kiểm tra thời gian (dùng dữ liệu từ Cache) ---
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

        // --- 3. Kiểm tra dịch vụ áp dụng ---
        if ($coupon->for_service_id !== null && $coupon->for_service_id != $serviceId) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_match_service")
            );
        }

        // --- 4. Kiểm tra giới hạn sử dụng ---
        // Kiểm tra giới hạn sử dụng toàn bộ
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return ServiceReturn::error(
                message: __("booking.coupon.usage_limit_reached")
            );
        }

        // --- 5. Kiểm tra giá trị tối đa của mã có vượt quá giá trị sau áp dụng không ---
        if ($coupon->is_percentage) {
            $discountAmount = ($priceBeforeDiscount * $coupon->discount_value) / 100;
        } else {
            $discountAmount = $priceBeforeDiscount - $coupon->discount_value;
        }
        // Kiểm tra giá trị giảm tối đa
        if ($discountAmount > $coupon->max_discount) {
            return ServiceReturn::error(
                message: __("booking.coupon.max_discount_exceeded")
            );
        }

        // --- 6. Kiểm tra thời gian sử dụng hợp lệ hay không ---
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
     * Phương thức kiểm tra tính hợp lệ của Coupon khi thu thập.
     * @param Coupon $coupon
     * @return ServiceReturn
     */
    public function validateCollectCoupon(Coupon $coupon): ServiceReturn
    {
        $today = now()->format('Y-m-d');
        $config = $coupon->config ?? [];
        $limit = $config['per_day_global'] ?? 0;
        $currentCollected = $config['daily_collected'][$today] ?? 0;
        if ($limit > 0 && $currentCollected >= $limit) {
           return ServiceReturn::error(
                message: __('validation.coupon.collect_limit_error', ['code' => $coupon->code])
            );
        }
        return ServiceReturn::success();
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
            $config = $coupon->config ?? [];
            $today = now()->format('Y-m-d');
            $history = $config['daily_collected'] ?? [];
            $history[$today] = ($history[$today] ?? 0) + 1;
            $config['daily_collected'] = $history;
            $coupon->update(['config' => $config]);
            return ServiceReturn::success();
        }catch (ServiceException $e) {
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
            $config = $coupon->config ?? [];
            $today = now()->format('Y-m-d');

            // Logic lưu lịch sử sử dụng thực tế (thanh toán thành công)
            $history = $config['daily_used'] ?? [];
            $history[$today] = ($history[$today] ?? 0) + 1;

            $config['daily_used'] = $history;

            $coupon->update([
                'used_count' => $coupon->used_count + 1,
                'config' => $config
            ]);
            return ServiceReturn::success();
        }catch (ServiceException $e) {
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }


    /**
     * Phương thức GHI (Write) - Kiểm tra, sử dụng, tăng used_count
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
            $successIncrement = $this->incrementUsedCount($coupon->id);

            if (!$successIncrement->isError()) {
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
            throw $e;
        }
    }
}
