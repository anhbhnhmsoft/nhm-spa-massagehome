<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Services\BookingService;

class BookingController extends BaseController
{
    public function __construct(
        protected BookingService $bookingService,
    )
    {
    }


    /**
     * Lấy danh sách đặt lịch
     */
    public function listBooking(ListRequest $request)
    {
        $dto = $request->getFilterOptions();

        $result = $this->bookingService->bookingPaginate($dto);
        $data = $result->getData();
        return $this->sendResponse(

        );
    }
}
