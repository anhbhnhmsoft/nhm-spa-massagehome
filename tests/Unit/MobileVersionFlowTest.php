<?php

use App\Support\MobileVersionFlow;

beforeEach(function () {
    MobileVersionFlow::forgetCachedVersions();
    MobileVersionFlow::forceCreateMinVersions([
        'ios' => '1.0.8',
        'android' => '1.0.8',
    ]);
});

afterEach(function () {
    MobileVersionFlow::forceCreateMinVersions(null);
});

test('booking application cancel flow is disabled when app metadata is missing', function () {
    expect(MobileVersionFlow::shouldUseBookingApplicationCancelFlow(null, null))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', null))->toBeFalse();
});

test('booking application cancel flow is enabled only for supported versions', function () {
    expect(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', '1.0.7'))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('android', '1.0.8'))->toBeTrue()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCancelFlow('ios', '1.0.8'))->toBeTrue();
});

test('booking application create flow uses configured latest app versions', function () {
    expect(MobileVersionFlow::shouldUseBookingApplicationCreateFlow('android', '1.0.7'))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCreateFlow('android', '1.0.8'))->toBeTrue()
        ->and(MobileVersionFlow::shouldUseBookingApplicationCreateFlow('ios', '1.0.8'))->toBeTrue();
});

test('modern mobile contract is enabled only for supported versions', function () {
    expect(MobileVersionFlow::shouldUseModernMobileContract('android', '1.0.7'))->toBeFalse()
        ->and(MobileVersionFlow::shouldUseModernMobileContract('android', '1.0.8'))->toBeTrue()
        ->and(MobileVersionFlow::shouldUseModernMobileContract('ios', '1.0.8'))->toBeTrue();
});
