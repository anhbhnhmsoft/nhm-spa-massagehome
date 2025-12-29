<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Requests\FormServiceRequest;
use App\Http\Resources\Booking\BookingItemResource;
use App\Http\Resources\Review\ReviewResource;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\DetailServiceResource;
use App\Http\Resources\Service\ServiceResource;
use App\Http\Resources\TotalIncome\TotalIncomeResource;
use App\Http\Resources\User\ProfileKTVResource;
use App\Services\BookingService;
use App\Services\ServiceService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class KTVController extends BaseController
{
    public function __construct(
        protected UserService    $userService,
        protected BookingService $bookingService,
        protected ServiceService $serviceService,
    ) {
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
        $dto->setSortBy('booking_time');
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
        $dto->addFilter('user_id', $request->user()->id);
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
     * @param FormServiceRequest $request
     * @return JsonResponse
     */
    public function addService(FormServiceRequest $request): JsonResponse
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

    /**
     * Cập nhật dịch vụ
     * @param FormServiceRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateService(FormServiceRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $result = $this->serviceService->updateService($id, $data);
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

    /**
     * Xóa dịch vụ
     * @param int $id
     * @return JsonResponse
     */
    public function deleteService(int $id): JsonResponse
    {
        $result = $this->serviceService->deleteService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: $result->getMessage(),
        );
    }

    /**
     * Lấy thông tin chi tiết dịch vụ
     * @param string $id
     * @return JsonResponse
     */
    public function detailService(string $id): JsonResponse
    {
        $result = $this->serviceService->detailService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new DetailServiceResource($data),
        );
    }
    /**
     * Lấy tổng thu nhập
     * @param Request $request
     * @return JsonResponse
     */
    public function totalIncome(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type'      => 'required|in:day,week,month,quarter,year',
        ], [
            'type.in' => __('validation.type.in'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray(),
            );
        }

        $validatedData = $validator->validated();

        $result = $this->bookingService->totalIncome($request->user(), $validatedData['type']);

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        $incomeData = $result->getData();

        return $this->sendSuccess(data: new TotalIncomeResource($incomeData));
    }

    /**
     * Lấy profile KTV
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = auth()->user();
        return $this->sendSuccess(data: new ProfileKTVResource($user));
    }
}
