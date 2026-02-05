<?php

namespace App\Jobs;

use App\Enums\Jobs\WalletTransCase;
use App\Enums\QueueKey;
use App\Services\Facades\TransactionJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WalletTransactionBookingJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    // Thời gian chờ giữa các lần thử lại (giây)
    public function backoff(): array
    {
        return [30, 60, 90];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int             $bookingId,
        protected WalletTransCase $case,
        protected array           $data = []
    )
    {
        $this->onQueue(QueueKey::TRANSACTIONS_PAYMENT);
    }

    /**
     * Thực hiện thanh toán booking sang trạng thái confirm, áp dụng coupon và cập nhật số lần sử dụng coupon.
     */
    public function handle(TransactionJobService $service): void
    {
        switch ($this->case) {
            case WalletTransCase::CONFIRM_BOOKING:
                try {
                    $result = $service->handleConfirmBooking($this->bookingId);
                    if ($result->isError()) {
                        $service->handleFailedConfirmBooking(
                            bookingId: $this->bookingId,
                        );
                    }
                } catch (\Throwable $exception) {
                    $service->handleFailedConfirmBooking(
                        bookingId: $this->bookingId,
                    );
                }
                break;
            case WalletTransCase::FINISH_BOOKING:
                $service->handleFinishBooking($this->bookingId);
                break;
            case WalletTransCase::CONFIRM_CANCEL_BOOKING:
                $service->handleConfirmCancelBooking(
                    $this->bookingId, $this->data);
                break;
        }
    }
}
