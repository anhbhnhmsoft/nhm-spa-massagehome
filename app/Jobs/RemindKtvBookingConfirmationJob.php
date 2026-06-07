<?php

namespace App\Jobs;

use App\Services\BookingApplicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemindKtvBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(
        protected int|string $bookingId,
        protected int $attemptNumber,
    ) {
        $this->onQueue('default');
    }

    public function handle(BookingApplicationService $service): void
    {
        $service->sendKtvConfirmationReminder(
            bookingId: (string) $this->bookingId,
            attemptNumber: $this->attemptNumber,
        );
    }
}
