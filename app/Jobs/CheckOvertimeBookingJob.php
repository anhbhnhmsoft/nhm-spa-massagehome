<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Repositories\BookingRepository;
use App\Services\BookingService;
use App\Core\LogHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CheckOvertimeBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(
        BookingRepository $bookingRepo,
        BookingService $bookingService
    ): void {
        try {
            // Lấy tất cả booking ONGOING
            $ongoingBookings = $bookingRepo->query()
                ->where('status', BookingStatus::ONGOING->value)
                ->get();

            foreach ($ongoingBookings as $booking) {
                $startTime = Carbon::parse($booking->start_time);
                $expectedEndTime = $startTime->copy()->addMinutes($booking->duration);
                $now = Carbon::now();

                // Tính thời gian quá hạn (âm = quá hạn)
                $overtimeMinutes = $now->diffInMinutes($expectedEndTime, false);

                // Nếu quá hạn hơn 10 phút → Tự động finish
                if ($overtimeMinutes <= -10) {
                    LogHelper::debug("Auto-finishing booking {$booking->id} - Overtime: " . abs($overtimeMinutes) . " minutes");

                    // Gửi thông báo cho KTV
                    SendNotificationJob::dispatch(
                        userId: $booking->ktv_user_id,
                        type: NotificationType::BOOKING_AUTO_FINISHED,
                        data: [
                            'booking_id' => $booking->id,
                            'overtime_minutes' => abs($overtimeMinutes),
                        ]
                    );

                    // Gửi thông báo cho customer
                    SendNotificationJob::dispatch(
                        userId: $booking->user_id,
                        type: NotificationType::BOOKING_AUTO_FINISHED,
                        data: [
                            'booking_id' => $booking->id,
                            'overtime_minutes' => abs($overtimeMinutes),
                        ]
                    );

                    // Auto finish booking
                    $result = $bookingService->finishBooking($booking->id,false);

                    if ($result->isError()) {
                        LogHelper::debug("Failed to auto-finish booking {$booking->id}: " . $result->getMessage());
                    } else {
                        LogHelper::debug("Successfully auto-finished booking {$booking->id}");
                    }
                }
                // Nếu quá hạn 5-10 phút → Gửi cảnh báo (chỉ 1 lần)
                elseif ($overtimeMinutes <= -5 && $overtimeMinutes > -10) {
                    // Kiểm tra đã gửi cảnh báo chưa
                    if (!$booking->overtime_warning_sent) {
                        LogHelper::debug("Sending overtime warning for booking {$booking->id} - Overtime: " . abs($overtimeMinutes) . " minutes");

                        SendNotificationJob::dispatch(
                            userId: $booking->ktv_user_id,
                            type: NotificationType::BOOKING_OVERTIME_WARNING,
                            data: [
                                'booking_id' => $booking->id,
                                'overtime_minutes' => abs($overtimeMinutes),
                            ]
                        );

                        // Đánh dấu đã gửi cảnh báo
                        $booking->overtime_warning_sent = true;
                        $booking->save();
                    }
                }
            }
        } catch (\Exception $e) {
            LogHelper::error("Error in CheckOvertimeBookingJob: " . $e->getMessage(), $e);
        }
    }
}
