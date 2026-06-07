<?php

use App\Support\MobileVersionFlow;

test('booking application cancel flow is disabled when app metadata is missing', function () {
    expect(MobileVersionFlow::shouldUseBookingApplicationCancelFlow(null, null))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', null))->toBeFalse();
});

test('booking application cancel flow is enabled only for supported versions', function () {
    expect(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', '1.0.7'))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', '1.0.8'))->toBeTrue()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('ios', '1.0.8'))->toBeTrue();
});
