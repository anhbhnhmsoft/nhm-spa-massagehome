<?php

use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\BookingApplicationService;
use App\Services\BookingService;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class);

afterEach(function () {
    Mockery::close();
});

it('routes legacy ktv cancel requests to booking service when app headers are missing', function () {
    $bookingService = Mockery::mock(BookingService::class);
    $applicationService = Mockery::mock(BookingApplicationService::class);

    $bookingService->shouldReceive('cancelBooking')
        ->once()
        ->with(123, 'Cancel reason')
        ->andReturn(ServiceReturn::success(message: 'cancelled'));

    $applicationService->shouldNotReceive('releaseBookingByKtv');

    $this->app->instance(BookingService::class, $bookingService);
    $this->app->instance(BookingApplicationService::class, $applicationService);

    Sanctum::actingAs(new User([
        'id' => 999,
        'role' => UserRole::KTV->value,
    ]));

    $this->postJson('/api/booking/cancel', [
        'booking_id' => 123,
        'reason' => 'Cancel reason',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', BookingStatus::CANCELED->value);
});

it('routes supported ktv app versions to immediate cancel flow', function () {
    $bookingService = Mockery::mock(BookingService::class);
    $applicationService = Mockery::mock(BookingApplicationService::class);

    $bookingService->shouldReceive('cancelBooking')
        ->once()
        ->with(123, 'Cancel reason')
        ->andReturn(ServiceReturn::success(message: 'cancelled'));

    $applicationService->shouldNotReceive('releaseBookingByKtv');

    $this->app->instance(BookingService::class, $bookingService);
    $this->app->instance(BookingApplicationService::class, $applicationService);

    Sanctum::actingAs(new User([
        'id' => 999,
        'role' => UserRole::KTV->value,
    ]));

    $this->withHeaders([
        'X-App-Platform' => 'android',
        'X-App-Version' => '1.0.8',
    ])->postJson('/api/booking/cancel', [
        'booking_id' => 123,
        'reason' => 'Cancel reason',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', BookingStatus::CANCELED->value);
});

it('always routes customer cancel requests to legacy booking service', function () {
    $bookingService = Mockery::mock(BookingService::class);
    $applicationService = Mockery::mock(BookingApplicationService::class);

    $bookingService->shouldReceive('cancelBooking')
        ->once()
        ->with(123, 'Cancel reason')
        ->andReturn(ServiceReturn::success(message: 'cancelled'));

    $applicationService->shouldNotReceive('releaseBookingByKtv');

    $this->app->instance(BookingService::class, $bookingService);
    $this->app->instance(BookingApplicationService::class, $applicationService);

    Sanctum::actingAs(new User([
        'id' => 999,
        'role' => UserRole::CUSTOMER->value,
    ]));

    $this->withHeaders([
        'X-App-Platform' => 'android',
        'X-App-Version' => '1.0.8',
    ])->postJson('/api/booking/cancel', [
        'booking_id' => 123,
        'reason' => 'Cancel reason',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', BookingStatus::CANCELED->value);
});

it('supports legacy ktv cancel-booking endpoint alias', function () {
    $bookingService = Mockery::mock(BookingService::class);
    $applicationService = Mockery::mock(BookingApplicationService::class);

    $bookingService->shouldReceive('cancelBooking')
        ->once()
        ->with(123, 'Cancel reason')
        ->andReturn(ServiceReturn::success(message: 'cancelled'));

    $applicationService->shouldNotReceive('releaseBookingByKtv');

    $this->app->instance(BookingService::class, $bookingService);
    $this->app->instance(BookingApplicationService::class, $applicationService);

    Sanctum::actingAs(new User([
        'id' => 999,
        'role' => UserRole::KTV->value,
    ]));

    $this->postJson('/api/ktv/cancel-booking', [
        'booking_id' => 123,
        'reason' => 'Cancel reason',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', BookingStatus::CANCELED->value);
});
