<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\BookingStatus;
use App\Http\Requests\API\Booking\BookingRequest;
use App\Http\Requests\API\Booking\PrepareBookingRequest;
use App\Http\Resources\Booking\BookingItemResource;
use App\Http\Resources\Booking\BookingApplicationResource;
use App\Enums\UserRole;
use App\Services\BookingApplicationService;
use App\Services\BookingService;
use App\Support\MobileVersionFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingApplicationService $bookingApplicationService,
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
     * Chuẩn bị đặt lịch (tính toán giá, thời gian)
     * @param PrepareBookingRequest $request
     * @return JsonResponse
     */
    public function prepareBooking(PrepareBookingRequest $request)
    {
        $data = $request->validated();

        $result = $this->bookingService->prepareBooking($data);
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
     * Đặt lịch hẹn dịch vụ
     * @param BookingRequest $request
     */
    public function booking(BookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $resultService = $this->bookingService->bookService($data);
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

    public function listApplications(ListRequest $request, int $bookingId): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->bookingApplicationService->listBookingApplicationsForCustomer((string) $bookingId, $dto);
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: BookingApplicationResource::collection($result->getData())->response()->getData()
        );
    }

    public function selectApplication(int $bookingId, int $applicationId): JsonResponse
    {
        $result = $this->bookingApplicationService->selectApplicationByCustomer(
            bookingId: (string) $bookingId,
            applicationId: (string) $applicationId,
        );
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new BookingItemResource($result->getData()),
            message: $result->getMessage()
        );
    }

    public function previewApplicationSelection(int $bookingId, int $applicationId): JsonResponse
    {
        $result = $this->bookingApplicationService->previewApplicationSelectionByCustomer(
            bookingId: (string) $bookingId,
            applicationId: (string) $applicationId,
        );
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(data: $result->getData());
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

    public function confirmKtvBooking(Request $request): JsonResponse
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

        $result = $this->bookingApplicationService->confirmBookingByKtv((string) $data['booking_id']);
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new BookingItemResource($result->getData()),
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

            $shouldUseBookingApplicationCancelFlow = $request->user()->role === UserRole::KTV->value
                && MobileVersionFlow::shouldUseBookingApplicationCancelFlow(
                    platform: $request->attributes->get('app_platform'),
                    version: $request->attributes->get('app_version'),
                );

            if ($shouldUseBookingApplicationCancelFlow) {
                $result = $this->bookingApplicationService->releaseBookingByKtv(
                    bookingId: (string) $data['booking_id'],
                    reason: $data['reason'] ?? null,
                );
            } else {
                $result = $this->bookingService->cancelBooking(
                    bookingId: $data['booking_id'],
                    reason: $data['reason'] ?? null,
                );
            }

            if ($result->isError()) {
                return $this->sendError(
                    message: $result->getMessage()
                );
            }

            $status = $shouldUseBookingApplicationCancelFlow
                ? BookingStatus::OPEN_FOR_APPLICATION
                : BookingStatus::WAITING_CANCEL;

            return $this->sendSuccess(
                data: [
                    'booking_id' => (int) $data['booking_id'],
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'canceled_at' => now()->format('Y-m-d H:i:s'),
                    'reason' => $data['reason'] ?? null,
                ],
                message: $result->getMessage()
            );
        } catch (\Exception $e) {
            return $this->sendError(
                message: $e->getMessage()
            );
        }
    }
}
