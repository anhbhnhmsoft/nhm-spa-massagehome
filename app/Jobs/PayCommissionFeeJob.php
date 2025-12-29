<?php

namespace App\Jobs;

use App\Core\LogHelper;
use App\Enums\QueueKey;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class PayCommissionFeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $bookingId
    ) {
        $this->onQueue(QueueKey::PAY_COMMISSION_FEE);
    }

    /**
     * Execute the job.
     */
    public function handle(BookingService $bookingService): void
    {
        $bookingService->payCommissionFee($this->bookingId);
    }

    /**
     * Xử lý khi job thất bại.
     */
    public function failed($exception)
    {
        LogHelper::error('PayCommissionFeeJob failed', $exception->getMessage(), [
            'booking_id' => $this->bookingId,
        ]);
    }
}
