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
     * @param BookingService $bookingService
     */
    public function handle(
        BookingService $bookingService
    ): void {
        try {
            // Lấy danh sách booking đang diễn ra
            $ongoingBookings = $bookingService->getOngoingBookings();

            foreach ($ongoingBookings as $booking) {
                // Xử lý từng booking
                $bookingService->processOvertimeBooking($booking);
            }
        } catch (\Exception $e) {
            LogHelper::error("Error in CheckOvertimeBookingJob: " . $e->getMessage(), $e);
        }
    }
}
