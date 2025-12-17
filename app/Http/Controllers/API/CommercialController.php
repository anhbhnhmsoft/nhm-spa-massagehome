<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Resources\Commercial\BannerResource;
use App\Services\CommercialService;
use App\Http\Resources\Service\CouponResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommercialController extends BaseController
{

    public function __construct(
        protected CommercialService $commercialService,
    ) {}

    /**
     * Lấy danh sách banner cho homepage
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBanner()
    {
        $result = $this->commercialService->getBanner();
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: BannerResource::collection($data));
    }

    /**
     * Lấy danh sách coupon ads cho homepage
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCouponAds()
    {
        $result = $this->commercialService->getCouponAds();
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: CouponResource::collection($data));
    }

    /**
     * Lấy danh sách coupon ads cho homepage
     * @return \Illuminate\Http\JsonResponse
     */
    public function collectCouponAds(Request $request): JsonResponse
    {
        $coupons = $request->validate(
            [
                'coupons' => 'required|array',
                'coupons.*' => 'required|exists:coupons,id',
            ],
            [
                'coupons.required' => __('validation.coupon.required'),
                'coupons.array' => __('validation.coupon.array'),
                'coupons.*.required' => __('validation.coupon.required'),
                'coupons.*.exists' => __('validation.coupon.exists'),
            ]
        );
        $result = $this->commercialService->collectCouponAds($coupons['coupons']);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: CouponResource::collection($data));
    }
}
