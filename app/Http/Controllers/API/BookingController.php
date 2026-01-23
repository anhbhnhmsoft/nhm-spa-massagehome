<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\BookingStatus;
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
        $data = $result->getData();
        return $this->sendSuccess(
            data: new BookingItemResource($data)
        );
    }

    /**
     * Bắt đầu thực hiện booking
     * @param Request $request
     * @return JsonResponse
     */
    public function startBooking(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'booking_id' => 'required|numeric',
            ],
            [
                'booking_id.required' => __('booking.validate.required'),
                'booking_id.numeric' => __('booking.validate.invalid'),
            ]
        );
        $result = $this->bookingService->startBooking($data['booking_id']);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        $booking = $data['booking'];
        return $this->sendSuccess(
            data: [
                'booking_id' => $booking->id,
                'start_time' => $data['start_time'],
                'duration' => $data['duration'],
            ],
            message: __('booking.started_successfully')
        );
    }

    /**
     * Kết thúc booking
     * @param int $bookingId
     */
    public function finishBooking(Request $request): JsonResponse
    {
        $validateData = $request->validate(
            [
                'booking_id' => 'required|numeric',
            ],
            [
                'booking_id.required' => __('booking.validate.required'),
                'booking_id.numeric' => __('booking.validate.invalid'),
            ]
        );
        $result = $this->bookingService->finishBooking(
            bookingId: $validateData['booking_id'],
            proactive: true
        );
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: $result->getData(),
            message: $result->getMessage()
        );
    }

    /**
     * Hủy booking (hủy lịch đặt - admin duyệt sau)
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelBooking(Request $request): JsonResponse
    {
        try {

        $data = $request->validate(
            [
                'booking_id' => 'required|numeric',
                'reason' => 'required|string',
            ],
            [
                'booking_id.required' => __('booking.validate.required'),
                'booking_id.numeric' => __('booking.validate.invalid'),
                'reason.string' => __('booking.validate.reason'),
                'reason.required' => __('booking.validate.reason_required'),
            ]
        );
        $result = $this->bookingService->cancelBooking(
            bookingId: $data['booking_id'],
            reason: $data['reason'] ?? null,
        );
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: [
                'booking_id' => (int)$data['booking_id'],
                'status' => BookingStatus::CANCELED->value,
                'status_label' => BookingStatus::CANCELED->label(),
                'canceled_at' => now()->format('Y-m-d H:i:s'),
                'reason' => $data['reason'] ?? null,
            ],
            message: $result->getMessage()
        );
        }
        catch (\Exception $e) {
            return $this->sendError(
                message: $e->getMessage()
            );
        }
    }
}
