<?php

namespace App\Jobs;

use App\Core\LogHelper;
use App\Enums\BookingStatus;
use App\Enums\QueueKey;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WalletTransactionBookingJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function backoff(): array
    {
        return [30, 60, 90];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $bookingId,
        protected int | null  $couponId,
        protected int $userId,
        protected int $serviceId
    ) {
        $this->onQueue(QueueKey::TRANSACTIONS_PAYMENT);
    }

    /**
     * Thực hiện thanh toán booking, áp dụng coupon và cập nhật số lần sử dụng coupon.
     */
    public function handle(WalletService $walletService, CouponService $couponService): void
    {
        $walletService->paymentInitBooking($this->bookingId);
        if (isset($this->couponId)) {
            $couponService->useCoupon(
                $this->couponId,
                $this->userId,
                $this->serviceId,
                $this->bookingId
            );
        }
    }

    /**
     * Xử lý khi job thất bại.
     */
    public function failed(\Throwable $exception): void
    {
        LogHelper::error('WalletTransactionBookingJob failed', $exception, [
            'booking_id' => $this->bookingId,
            'coupon_id' => $this->couponId,
            'user_id' => $this->userId,
            'service_id' => $this->serviceId,
        ]);

        // Gọi service để cancel booking
        $bookingService = app(BookingService::class);
        $bookingService->cancelBooking(
            $this->bookingId,
            BookingStatus::PAYMENT_FAILED,
            $exception->getMessage(),
            false
        );
    }
}
