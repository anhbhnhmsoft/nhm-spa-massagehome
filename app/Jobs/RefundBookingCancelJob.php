<?php

namespace App\Jobs;

use App\Core\LogHelper;
use App\Enums\QueueKey;
use App\Services\BookingService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RefundBookingCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $bookingId,
        public ?string $reason = null
    ) {
        $this->onQueue(QueueKey::REFUND_BOOKING_CANCEL);
    }

    /**
     * Execute the job.
     */
    public function handle(BookingService $bookingService): void
    {
        $bookingService->refundCancelBooking($this->bookingId, $this->reason);
    }

    public function failed(\Exception $exception): void
    {
        LogHelper::error(
            message: "Lỗi RefundBookingCancelJob@handle",
            ex: $exception
        );
    }
}
