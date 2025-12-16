<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\BannerRepository;
use App\Repositories\CouponRepository;
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
            $coupons = $this->couponRepository
                ->queryCoupon()
                ->where('display_ads', true)
                ->whereJsonLength('banners', 3)
                ->get();
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
     * Lấy danh sách coupon ads cho homepage
     * @param array $coupons
     * @return ServiceReturn
     */
    public function collectCouponAds(array $coupons): ServiceReturn
    {
        try {
            $user = Auth::user();


            $coupons = $this->couponRepository->queryCoupon()->whereIn('id', $coupons)->get();
            /**
             * @var \App\Models\User $user
             */
            $user->collectionCoupons()->syncWithoutDetaching(
                $coupons->pluck('id')->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])
            );
            return ServiceReturn::success(
                data: $user->collectionCoupons()->get()
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
