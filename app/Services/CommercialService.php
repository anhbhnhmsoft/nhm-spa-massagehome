<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BannerType;
use App\Repositories\BannerRepository;
use App\Repositories\CouponRepository;
use App\Repositories\StaticContractRepository;
use Illuminate\Support\Facades\App;

class CommercialService extends BaseService
{
    public function __construct(
        protected BannerRepository $bannerRepository,
        protected CouponRepository $couponRepository,
        protected CouponService $couponService,
        protected StaticContractRepository $staticContractRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy danh sách banner cho homepage
     * @param BannerType $type
     * @return ServiceReturn
     */
    public function getBanner(BannerType $type): ServiceReturn
    {
        try {
            $banners = $this->bannerRepository->queryBanner()
                ->where('type', $type)
                ->get();
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
     * @param int $couponId
     * @return ServiceReturn
     */
    public function collectCoupon(int $couponId): ServiceReturn
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return ServiceReturn::success(
                   data: [
                       'need_login' => true,
                    ]
                );
            }
            // Lấy coupon
            $coupon = $this->couponRepository
                ->filterQuery($this->couponRepository->queryCoupon(), ['is_valid' => true])
                ->where('id', $couponId)
                ->first();
            if (!$coupon) {
                throw new ServiceException(__("error.coupon_not_found"));
            }
            // Kiểm tra xem User đã có Coupon này chưa
            $alreadyHas = $user->collectionCoupons()->where('coupon_id', $coupon->id)->exists();
            if ($alreadyHas) {
                return ServiceReturn::success(
                    data: [
                        'already_collected' => true,
                    ]
                );
            }
            // Kiểm tra tính hợp lệ của Coupon khi thu thập
            $validateResult = $this->couponService->validateCollectCoupon($coupon);
            if ($validateResult->isError()) {
                throw new ServiceException($validateResult->getMessage());
            }
            // Tăng số lần thu thập Coupon
            $isIncremented = $this->couponService->incrementCollectCount($coupon->id);
            if ($isIncremented->isError()) {
                throw new ServiceException(__('validation.coupon.collect_error', ['code' => $coupon->code]));
            }
            // Ghi vào "ví" người dùng
            $user->collectionCoupons()->syncWithoutDetaching([
                $coupon->id => ['is_used' => false],
            ]);
            return ServiceReturn::success();
        }catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi CommercialService@collectCoupon",
                ex: $exception,
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }
}
