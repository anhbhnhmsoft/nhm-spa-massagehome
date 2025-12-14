<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Models\Coupon;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService extends BaseService
{
    protected const COUPON_CACHE_TTL = 86400; // 24 hours

    public function __construct(
        protected CouponRepository $couponRepository,
        protected CouponUsedRepository $couponUsedRepository,
    ) {
        parent::__construct();
    }

    /**
     * Đồng bộ Coupon và số lần sử dụng của User vào Cache.
     * Phương thức này CHỈ được gọi khi có giao dịch GHI (sử dụng thành công).
     *
     * @param Coupon $coupon
     * @param string $userId
     * @param int $usageCountForUser Số lần sử dụng của User (đã được cập nhật)
     * @return void
     */
    protected function syncCouponToCache(Coupon $coupon, string $userId, int $usageCountForUser): void
    {
        // 1. Lưu thông tin Coupon (Bao gồm used_count mới nhất)
        $couponData = $coupon->toArray();
        Caching::setCache(CacheKey::CACHE_COUPON, $couponData, $coupon->id, self::COUPON_CACHE_TTL);

        // 2. Lưu số lần sử dụng của User
        Caching::setCache(CacheKey::CACHE_COUPON_USED, $usageCountForUser, "{$coupon->id}:{$userId}", self::COUPON_CACHE_TTL * 15);
    }

    /**
     * Lấy Coupon từ Cache. Nếu không có, query DB và lưu lại vào Cache.
     *
     * @param string $couponId
     * @return array|null
     */
    protected function getCouponFromCache(string $couponId): ?array
    {
        // Giả định Caching::getCache(key_group, key_suffix)
        $couponData = Caching::getCache(CacheKey::CACHE_COUPON, $couponId);

        if ($couponData) {
            return $couponData;
        }

        // Cache miss: Truy vấn DB và lưu lại Cache
        /** @var Coupon|null $coupon */
        $coupon = $this->couponRepository->query()->find($couponId);

        if ($coupon) {
            $couponData = $coupon->toArray();
            Caching::setCache(CacheKey::CACHE_COUPON, $couponData, $coupon->id, self::COUPON_CACHE_TTL);
            return $couponData;
        }

        return null;
    }

    /**
     * Lấy số lần sử dụng của User từ Cache.
     * * @param string $couponId
     * @param string $userId
     * @return int
     */
    protected function getUserUsageFromCache(string $couponId, string $userId): int
    {
        $cacheKey = "{$couponId}:{$userId}";

        return (int) Caching::getCache(CacheKey::CACHE_COUPON_USED, $cacheKey);
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
    public function validateCouponWithCache(string $couponId, string $userId, string $serviceId, float $priceBeforeDiscount): ServiceReturn
    {

        if ($this->getUserUsageFromCache($couponId, $userId) >= 1) {
            return ServiceReturn::error(
                message: __("booking.coupon.used")
            );
        }
        $coupon = $this->getCouponFromCache($couponId);

        if (!$coupon) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_found")
            );
        }


        // --- 1. Kiểm tra trạng thái và thời gian (dùng dữ liệu từ Cache) ---
        if (!$coupon['is_active']) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_active")
            );
        }

        $now = Carbon::now();
        if ($now->isBefore(Carbon::parse($coupon['start_at']))) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_yet_started")
            );
        }
        if ($now->isAfter(Carbon::parse($coupon['end_at']))) {
            return ServiceReturn::error(
                message: __("booking.coupon.expired")
            );
        }

        // --- 2. Kiểm tra điều kiện áp dụng ---
        if ($coupon['for_service_id'] !== null && $coupon['for_service_id'] != $serviceId) {
            return ServiceReturn::error(
                message: __("booking.coupon.not_match_service")
            );
        }

        // --- 3. Kiểm tra giới hạn sử dụng (dùng dữ liệu từ Cache) ---
        // Kiểm tra giới hạn sử dụng toàn bộ
        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ServiceReturn::error(
                message: __("booking.coupon.usage_limit_reached")
            );
        }

        // --- 4. Kiểm tra giá trị tối đa của mã có vượt quá giá trị sau áp dụng không ---

        if ($coupon['is_percentage']) {
            $discountAmount = ($priceBeforeDiscount * $coupon['discount_value']) / 100;
        } else {
            $discountAmount = $priceBeforeDiscount - $coupon['discount_value'];
        }

        if ($discountAmount > $coupon['max_discount']) {
            return ServiceReturn::error(
                message: __("booking.coupon.max_discount_exceeded")
            );
        }

        // // --- 4. Tính toán giá trị giảm giá tối đa có thể áp dụng ---
        // $discountAmount = 0.0;

        // if ($coupon['is_percentage']) {
        //     $discountAmount = ($priceBeforeDiscount * $coupon['discount_value']) / 100;
        //     if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
        //         $discountAmount = $coupon['max_discount'];
        //     }
        // } else {
        //     $discountAmount = min($coupon['discount_value'], $priceBeforeDiscount);
        // }

        // if ($discountAmount <= 0) {
        //     // Thường xảy ra khi giá trị giảm giá là 0 hoặc âm  
        //     return ServiceReturn::error(
        //         message: __("booking.coupon.is_invalid")
        //     );
        // }

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
    public function useCouponAndSyncCache(
        string $couponId,
        string $userId,
        string $serviceId,
        string $bookingId,
    ): ServiceReturn {
        DB::beginTransaction();
        try {

            // 1. Lấy Coupon với
            /** @var Coupon|null $coupon */
            $coupon = $this->couponRepository->query()
                ->where('id', $couponId)
                ->lockForUpdate()
                ->first();

            if (!$coupon) {
                throw new ServiceException(__("booking.coupon.not_found"));
            }

            // 2. Tái kiểm tra giới hạn sử dụng 
            if ($coupon->usage_limit != null && $coupon->used_count >= $coupon->usage_limit) {
                throw new ServiceException(__("booking.coupon.usage_limit_reached"));
            }

            // Tái kiểm tra giới hạn sử dụng trên mỗi người dùng bằng cách truy vấn DB
            $dbUserUsageCount = $this->couponUsedRepository->query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->count();

            if ($dbUserUsageCount >= 1) {
                throw new ServiceException(__("booking.coupon.used"));
            }


            // 3. Cập nhật số lượng sử dụng trong DB (Tăng lên 1)
            $coupon->used_count = $coupon->used_count + 1;
            $coupon->save();

            // 4. Ghi lịch sử sử dụng vào db
            $couponUsed = $this->couponUsedRepository->create([
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'service_id' => $serviceId,
                'booking_id' => $bookingId,
            ]);

            // 5. Đồng bộ dữ liệu Coupon và User Usage Count mới nhất vào Cache
            $this->syncCouponToCache($coupon, $userId, $dbUserUsageCount + 1);

            DB::commit();

            return ServiceReturn::success(
                message: __("booking.coupon.used_successfully")
            );
        } catch (ServiceException $e) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            return ServiceReturn::error(
                message: __("common_error.server_error"),
                exception: $e
            );
        }
    }
}
