<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\ServiceDuration;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\CouponResource;
use App\Http\Resources\Service\CouponUserResource;
use App\Http\Resources\Service\ServiceResource;
use App\Http\Resources\User\CustomerBookedTodayResource;
use App\Services\BookingService;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;


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
     * Lấy danh sách dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listServices(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->serviceService->servicePaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: ServiceResource::collection($data)->response()->getData()
        );
    }

    /**
     * Lấy chi tiết dịch vụ
     * @param int $id
     * @return JsonResponse
     */
    public function detailService(int $id): JsonResponse
    {
        $result = $this->serviceService->getDetailService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ServiceResource($data)
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



    // cần queue transactions-payment để ghi nhận giao dịch

    /**
     * Đặt lịch hẹn dịch vụ
     * @param Request $request
     */
    public function booking(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'service_id' => ['required', 'numeric', 'exists:services,id'],
            'option_id' => ['required', 'numeric', 'exists:service_options,id'],
            // Rule: Phải là định dạng ngày & Phải sau thời điểm hiện tại 1 tiếng
            'book_time' => [
                'required',
                'date',
            ],
            // Validate Coupon (Không bắt buộc, nhưng nếu có phải tồn tại)
            'coupon_id' => [
                'nullable',
                'string',
                'exists:coupons,id',
            ],

            // 4. Validate Địa chỉ & Note
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'note_address' => ['nullable', 'string', 'max:500'],
            // 5. Validate Tọa độ (Lat/Lng)
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'service_id.required' => __('validation.service_id.required'),
            'service_id.numeric' => __('validation.service_id.numeric'),
            'service_id.exists' => __('validation.service_id.exists'),
            'option_id.required' => __('validation.option_id.required'),
            'option_id.numeric' => __('validation.option_id.numeric'),
            'option_id.exists' => __('validation.option_id.exists'),
            'book_time.required' => __('validation.book_time.required'),
            'book_time.timestamp' => __('validation.book_time.timestamp'),
            'coupon_id.exists' => __('validation.coupon_id.exists'),
            'note.max' => __('validation.note.max'),
            'note_address.max' => __('validation.note_address.max'),
            'latitude.required' => __('validation.latitude.required'),
            'longitude.required' => __('validation.longitude.required'),
        ]);
        $resultService = $this->bookingService->bookService(
            serviceId: $validate['service_id'],
            optionId: $validate['option_id'],
            address: $validate['address'],
            latitude: $validate['latitude'],
            longitude: $validate['longitude'],
            bookTime: $validate['book_time'],
            note: $validate['note'] ?? null,
            noteAddress: $validate['note_address'] ?? null,
            couponId: $validate['coupon_id'] ?? null,
        );
        if ($resultService->isError()) {
            return $this->sendError(
                message: $resultService->getMessage()
            );
        }
        return $this->sendSuccess(
            data: $resultService->getData()
        );
    }

    /**
     * Lấy danh sách khách hàng đã đặt lịch trong ngày hôm nay
     * với status COMPLETED hoặc ONGOING
     * @return JsonResponse
     */
    public function getTodayBookedCustomers(string $id): JsonResponse
    {
        $result = $this->serviceService->getTodayBookedCustomers($id);

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }

        $data = $result->getData();
        return $this->sendSuccess(
            data: CustomerBookedTodayResource::collection($data)
        );
    }
}
