<?php

namespace App\Jobs;

use App\Services\BookingApplicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireOpenApplicationBookingJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(
        protected int|string $bookingId,
    ) {
        $this->onQueue('default');
    }

    public function handle(BookingApplicationService $service): void
    {
        $service->expireOpenApplicationBooking((string) $this->bookingId);
    }
}
