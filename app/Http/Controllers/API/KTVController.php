<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\Language;
use App\Enums\ServiceDuration;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Resources\Booking\BookingItemResource;
use App\Http\Resources\Review\ReviewResource;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\ServiceResource;
use App\Services\BookingService;
use App\Services\ServiceService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class KTVController extends BaseController
{
    public function __construct(
        protected UserService    $userService,
        protected BookingService $bookingService,
        protected ServiceService $serviceService,
    )
    {
        /**
         * Tất cả các endpoint trong controller này đều yêu cầu quyền KTV, qua validate middleware CheckKtv
         */
    }

    /**
     * Lấy thông tin dashboard của KTV
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $result = $this->userService->dashboardKtv();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        $booking = $data['booking'];
        $totalRevenueToday = $data['total_revenue_today'];
        $totalRevenueYesterday = $data['total_revenue_yesterday'];
        $totalBookingCompletedToday = $data['total_booking_completed_today'];
        $totalBookingPendingToday = $data['total_booking_pending_today'];
        $reviewToday = $data['review_today'];
        return $this->sendSuccess(
            data: [
                'booking' => $booking ? new BookingItemResource($booking) : null,
                'total_revenue_today' => $totalRevenueToday,
                'total_revenue_yesterday' => $totalRevenueYesterday,
                'total_booking_completed_today' => $totalBookingCompletedToday,
                'total_booking_pending_today' => $totalBookingPendingToday,
                'review_today' => $reviewToday ? ReviewResource::collection($reviewToday)->toArray($request) : null,
            ],
            message: $result->getMessage() ?? __('common.success.data_created')
        );
    }

    /**
     * Lấy danh sách booking của KTV
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listBooking(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('ktv_user_id', $request->user()->id);
        $dto->setSortBy('created_at');
        $dto->setDirection('desc');
        $result = $this->bookingService->bookingPaginate($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: BookingItemResource::collection($data)->response()->getData(),
        );
    }

    /**
     * Lấy danh sách service của KTV
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listService(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('ktv_user_id', $request->user()->id);
        $dto->setSortBy('created_at');
        $dto->setDirection('desc');
        $result = $this->serviceService->servicePaginate($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: ServiceResource::collection($data)->response()->getData(),
        );
    }

     /**
     * Lấy tất cả categories
     * @param Request $request
     * @return JsonResponse
     */
    public function allCategories(Request $request): JsonResponse
    {
        $result = $this->serviceService->allCategories();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: CategoryResource::collection($data)->toArray($request),
        );
    }

    /**
     * Thêm dịch vụ mới
     * @param CreateServiceRequest $request
     * @return JsonResponse
     */
    public function addService(CreateServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $result = $this->serviceService->createService($data);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ServiceResource($data),
        );
    }
}
