<?php

use App\Enums\BookingStatus;
use App\Jobs\ExpireKtvConfirmationJob;
use App\Jobs\RemindKtvBookingConfirmationJob;
use App\Jobs\SendNotificationJob;
use App\Services\BookingApplicationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

it('schedules initial ktv confirmation notification and reminder jobs', function () {
    Carbon::setTestNow('2026-06-08 10:00:00');
    Bus::fake([
        SendNotificationJob::class,
        ExpireKtvConfirmationJob::class,
        RemindKtvBookingConfirmationJob::class,
    ]);

    $bookingRepository = Mockery::mock(\App\Repositories\BookingRepository::class);
    $bookingApplicationRepository = Mockery::mock(\App\Repositories\BookingApplicationRepository::class);
    $userRepository = Mockery::mock(\App\Repositories\UserRepository::class);
    $couponRepository = Mockery::mock(\App\Repositories\CouponRepository::class);
    $configService = Mockery::mock(\App\Services\ConfigService::class);
    $walletValidator = Mockery::mock(\App\Services\Validator\WalletValidator::class);

    $service = new BookingApplicationService(
        $bookingRepository,
        $bookingApplicationRepository,
        $userRepository,
        $couponRepository,
        $configService,
        $walletValidator,
    );

    $booking = Mockery::mock(\App\Models\ServiceBooking::class)->makePartial();
    $booking->id = 1001;
    $booking->ktv_user_id = 2002;
    $booking->status = BookingStatus::PENDING->value;
    $booking->booking_time = Carbon::parse('2026-06-08 14:00:00');
    $booking->shouldReceive('save')->times(1);
    $booking->shouldReceive('getAttribute')->andReturnUsing(function (string $key) use ($booking) {
        return $booking->{$key} ?? null;
    });
    $booking->setRelation('user', (object) ['name' => 'Customer A']);

    $service->markWaitingKtvConfirm($booking);

    expect($booking->status)->toBe(BookingStatus::WAITING_KTV_CONFIRM->value)
        ->and($booking->ktv_confirm_deadline_at?->format('Y-m-d H:i:s'))->toBe('2026-06-08 10:03:00')
        ->and($booking->application_opened_at)->toBeNull()
        ->and($booking->application_open_reason)->toBeNull();

    Bus::assertDispatchedTimes(SendNotificationJob::class, 1);
    Bus::assertDispatched(ExpireKtvConfirmationJob::class);
    Bus::assertDispatchedTimes(RemindKtvBookingConfirmationJob::class, 2);
});
