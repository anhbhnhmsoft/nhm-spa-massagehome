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
     * @return JsonResponse
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
     * @return JsonResponse
     */
    public function getCouponAds(): JsonResponse
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
     * @param int $couponId
     * @return JsonResponse
     */
    public function collectCouponAds(int $couponId): JsonResponse
    {
        $result = $this->commercialService->collectCoupon($couponId);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: $data);
    }
}
