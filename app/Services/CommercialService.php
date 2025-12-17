<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\Language;
use App\Repositories\BannerRepository;
use App\Repositories\CouponRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class CommercialService extends BaseService
{
    public function __construct(
        protected BannerRepository $bannerRepository,
        protected CouponRepository $couponRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy danh sách banner cho homepage
     * @return ServiceReturn
     */
    public function getBanner(): ServiceReturn
    {
        try {
            $banners = $this->bannerRepository->queryBanner()->get();
            return ServiceReturn::success(
                data: $banners
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi CommercialService@getBanner",
                ex: $exception,
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Lấy danh sách coupon ads cho homepage
     * @return ServiceReturn
     */
    public function getCouponAds(): ServiceReturn
    {
        try {
            $language = App::getLocale();
            $query = $this->couponRepository
                ->queryCoupon()
                ->where('display_ads', true)
                ->whereNotNull('banners->' . $language);

            $coupons = $this->couponRepository->filterQuery($query, ['is_valid' => true])->get();
            return ServiceReturn::success(
                data: $coupons
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi CommercialService@getCouponAds",
                ex: $exception,
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Thu thập mã giảm giá
     * @param array $couponIds
     * @return ServiceReturn
     */
    public function collectCouponAds(array $couponIds): ServiceReturn
    {
        try {
            /**
             * @var User $user
             */
            $user = Auth::user();
            $today = now()->format('Y-m-d');
            // 1. Lấy danh sách Coupon
            $coupons = $this->couponRepository
                ->filterQuery($this->couponRepository->queryCoupon(), ['is_valid' => true])
                ->whereIn('id', $couponIds)
                ->get();

            $collectedIds = [];

            foreach ($coupons as $coupon) {
                // 2. Kiểm tra xem User đã có Coupon này chưa
                $alreadyHas = $user->collectionCoupons()->where('coupon_id', $coupon->id)->exists();
                if ($alreadyHas) continue;

                // 2. Kiểm tra giới hạn thu thập trong ngày (Daily Collect Limit)
                $config = $coupon->config ?? [];
                $limit = $config['per_day_global'] ?? 0;
                $currentCollected = $config['daily_collected'][$today] ?? 0;

                if ($limit > 0 && $currentCollected >= $limit) {
                    $errors[] = __('validation.coupon.collect_limit_error', ['code' => $coupon->code]);
                    continue;
                }

                // 3. Thực hiện tăng số lượng Atomic
                $isIncremented = $this->couponRepository->incrementDailyCollectCountAtomic($coupon->id);

                if ($isIncremented) {
                    $collectedIds[] = $coupon->id;
                } else {
                    $errors[] = __('validation.coupon.collect_error', ['code' => $coupon->code]);
                }
            }

            // 4. Ghi vào "ví" người dùng
            if (!empty($collectedIds)) {
                $syncData = collect($collectedIds)->mapWithKeys(fn($id) => [$id => ['is_used' => false]]);
                $user->collectionCoupons()->syncWithoutDetaching($syncData->toArray());
            }

            return ServiceReturn::success(
                data: [
                    'collectedCoupons' => $user->collectionCoupons()->get(),
                    'errors' => $errors
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi CommercialService@collectCouponAds",
                ex: $exception,
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }
}
