<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\ServiceDuration;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\CouponResource;
use App\Http\Resources\Service\ServiceResource;
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
     * Đặt lịch hẹn dịch vụ
     * @param Request $request
     */
    public function booking(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'service_id' => ['required', 'numeric', 'exists:services,id'],
            // Rule: Phải là định dạng ngày & Phải sau thời điểm hiện tại 1 tiếng
            'book_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $bookingTime = Carbon::parse($value)->setTimezone(config('app.timezone'));
                    // Kiểm tra xem thời gian đặt có hợp lệ không
                    $validateTime = now()->addHour()->setTimezone(config('app.timezone'));
                    if (
                        $bookingTime->isBefore($validateTime)
                    ) {
                        $fail(__('validation.book_time.after'));
                    }
                }
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
            // 5. Validate Tọa độ (Lat/Lng)
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            // 6. Validate Duration (Thời lượng phút)
            'duration' => ['required', Rule::in(
                array_column(ServiceDuration::cases(), 'value')
            )],
        ],[
            'service_id.required' => __('validation.service_id.required'),
            'service_id.numeric' => __('validation.service_id.numeric'),
            'service_id.exists' => __('validation.service_id.exists'),
            'book_time.required' => __('validation.book_time.required'),
            'book_time.date' => __('validation.book_time.date'),
            'coupon_id.exists' => __('validation.coupon_id.exists'),
            'address.required' => __('validation.address.required'),
            'lat.required' => __('validation.lat.required'),
            'lng.required' => __('validation.lng.required'),
            'duration.required' => __('validation.duration.required'),
            'duration.in' => __('validation.duration.in'),
        ]);
        $resultService = $this->bookingService->bookService(
            serviceId: $validate['service_id'],
            duration: ServiceDuration::from($validate['duration']),
            address: $validate['address'],
            lat: $validate['lat'],
            bookTime: $validate['book_time'],
            lng: $validate['lng'],
            note: $validate['note'] ?? null,
            couponId: $validate['coupon_id'] ?? null,
        );
        if ($resultService->isError()) {
            return $this->sendError(
                message: $resultService->getMessage()
            );
        }
        return $this->sendSuccess();
    }

}
