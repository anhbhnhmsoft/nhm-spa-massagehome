<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\CouponResource;
use App\Http\Resources\Service\CouponUserResource;
use App\Services\BookingService;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ServiceController extends BaseController
{
    public function __construct(
        protected ServiceService $serviceService,
        protected BookingService $bookingService,
    )
    {
    }

    /**
     * Lấy danh sách danh mục dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listCategory(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->serviceService->categoryPaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: CategoryResource::collection($data)->response()->getData()
        );
    }

    /**
     * Lấy danh sách mã giảm giá
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listCoupon(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        // Lọc chỉ lấy mã giảm giá hợp lệ
        $dto->addFilter('is_valid', true);
        // Lấy toàn bộ mã giảm giá (không phân trang)
        $dto->addFilter('get_all', true);
        // Lọc chỉ lấy mã giảm giá chưa được sử dụng
        $dto->addFilter('user_id_is_not_used', $request->user()->id);

        $result = $this->serviceService->getListCoupon($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: CouponResource::collection($data)
        );
    }


    /**
     * Lấy danh sách mã giảm giá của người dùng
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function myListCoupon(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        // Lọc chỉ lấy mã giảm giá chưa được sử dụng
        $dto->addFilter('is_used', false);
        // Lấy toàn bộ mã giảm giá của người dùng
        $dto->addFilter('user_id', $request->user()->id);

        $result = $this->serviceService->couponUserPaginate($dto);

        $data = $result->getData();

        return $this->sendSuccess(
            data: CouponUserResource::collection($data)->response()->getData()
        );
    }

}
