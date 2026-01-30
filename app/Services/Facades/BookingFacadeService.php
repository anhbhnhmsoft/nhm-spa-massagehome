<?php

namespace App\Services\Facades;

use App\Core\LogHelper;
use App\Core\Service\ServiceReturn;
use App\Enums\NotificationAdminType;
use App\Services\BookingService;
use App\Services\NotificationService;

class BookingFacadeService
{
    public function __construct(
        protected BookingService      $bookingService,
        protected NotificationService $notificationService,
    )
    {
    }

    /**
     * Kiểm tra các booking quá hạn
     * @param int $overdueMinutes - Số phút quá hạn
     * @return ServiceReturn
     */
    public function checkOverdueOnGoingBookings(int $overdueMinutes = 30): ServiceReturn
    {
        try {
            // Lấy danh sách booking quá hạn mà KTV vẫn chưa hoàn thành
            $overdueBookings = $this->bookingService->getBookingRepository()->checkOverdueOnGoingBookings($overdueMinutes);
            if ($overdueBookings->isEmpty()) {
                return ServiceReturn::success();
            }
            $overdueBookings->each(function ($booking) use ($overdueMinutes) {
                // Gửi thông báo cho KTV
                $this->notificationService->sendAdminNotification(
                    type: NotificationAdminType::OVERDUE_ONGOING_BOOKING,
                    data: [
                        'booking_id' => $booking->id,
                        'start_time' => $booking->start_time->format('H:i:s d/m/Y'),
                        'duration' => $booking->duration,
                        'overdue_minutes' => $overdueMinutes,
                    ]
                );
            });

            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi BookingFacadeService@checkOverdueBookings",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Kiểm tra các booking quá hạn đã xác nhận
     * @param int $overdueMinutes - Số phút quá hạn
     * @return ServiceReturn
     */
    public function checkOverdueConfirmedBookings(int $overdueMinutes = 30): ServiceReturn
    {
        try {
            // Lấy danh sách booking quá hạn mà KTV vẫn chưa hoàn thành
            $overdueBookings = $this->bookingService->getBookingRepository()->checkOverdueConfirmedBookings($overdueMinutes);
            if ($overdueBookings->isEmpty()) {
                return ServiceReturn::success();
            }
            $overdueBookings->each(function ($booking) use ($overdueMinutes) {
                // Gửi thông báo cho KTV
                $this->notificationService->sendAdminNotification(
                    type: NotificationAdminType::OVERDUE_CONFIRMED_BOOKING,
                    data: [
                        'booking_id' => $booking->id,
                        'booking_time' => $booking->booking_time->format('H:i:s d/m/Y'),
                        'duration' => $booking->duration,
                        'overdue_minutes' => $overdueMinutes,
                    ]
                );
            });

            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi BookingFacadeService@checkOverdueConfirmedBookings",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }
}
