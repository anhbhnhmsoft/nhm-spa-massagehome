<?php

namespace App\Services\Facades;

use App\Core\LogHelper;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\NotificationAdminType;
use App\Jobs\SendNotificationAdminJob;
use App\Services\BookingService;
use App\Services\ConfigService;
use App\Services\NotificationService;

class BookingFacadeService
{
    public function __construct(
        protected BookingService      $bookingService,
        protected NotificationService $notificationService,
        protected ConfigService $configService,
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
                SendNotificationAdminJob::dispatch(
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
            // Lấy khoảng thời gian nghỉ giữa các booking
            $breakTimeGap = $this->configService->getConfigValue(ConfigName::BREAK_TIME_GAP);
            // Lấy danh sách booking quá hạn mà KTV vẫn chưa hoàn thành
            $overdueBookings = $this->bookingService->getBookingRepository()->checkOverdueConfirmedBookings($overdueMinutes + $breakTimeGap);
            if ($overdueBookings->isEmpty()) {
                return ServiceReturn::success();
            }
            $overdueBookings->each(function ($booking) use ($overdueMinutes) {
                // Gửi thông báo cho KTV
                SendNotificationAdminJob::dispatch(
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

    /**
     * Tự hoàn thành các booking ongoing đã quá hạn ở ngưỡng riêng của hệ thống.
     * @return ServiceReturn
     */
    public function autoFinishOverdueOnGoingBookings(): ServiceReturn
    {
        try {
            $autoFinishMinutes = (int) ($this->configService->getConfigValue(ConfigName::AUTO_FINISH_ONGOING_BOOKING_MINUTES) ?? 60);
            $overdueBookings = $this->bookingService
                ->getBookingRepository()
                ->getAutoFinishOverdueOnGoingBookings($autoFinishMinutes);

            if ($overdueBookings->isEmpty()) {
                return ServiceReturn::success(
                    data: [
                        'processed' => 0,
                        'failed' => 0,
                    ]
                );
            }

            $processed = 0;
            $failed = 0;

            $overdueBookings->each(function ($booking) use (&$processed, &$failed) {
                $result = $this->bookingService->finishBookingBySystem((int) $booking->id);
                if ($result->isError()) {
                    $failed++;
                    LogHelper::error(
                        message: "Auto finish ongoing booking failed: {$booking->id}. {$result->getMessage()}",
                    );
                    return;
                }

                $processed++;
            });

            LogHelper::debug("Auto finish ongoing bookings completed. Processed: {$processed}, Failed: {$failed}");

            return ServiceReturn::success(
                data: [
                    'processed' => $processed,
                    'failed' => $failed,
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi BookingFacadeService@autoFinishOverdueOnGoingBookings",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }
}
