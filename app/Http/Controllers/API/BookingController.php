<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Booking\BookingItemResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}


    /**
     * Lấy danh sách đặt lịch
     */
    public function listBooking(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('user_id', $request->user()->id);
        $dto->addFilter('count_reviews_by_this_user_id', true);
        $result = $this->bookingService->bookingPaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: BookingItemResource::collection($data)->response()->getData()
        );
    }

    /**
     * Kiểm tra booking
     * @param string $bookingId
     */
    public function checkBooking(string $bookingId)
    {
        $result = $this->bookingService->checkBooking($bookingId);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: $result->getData()
        );
    }

    /**
     * Lấy chi tiết thông tin booking
     * @param int $bookingId
     */
    public function detailBooking(int $bookingId): JsonResponse
    {
        $result = $this->bookingService->detailBooking($bookingId);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: BookingItemResource::make($result->getData())->response()->getData()
        );
    }
}
