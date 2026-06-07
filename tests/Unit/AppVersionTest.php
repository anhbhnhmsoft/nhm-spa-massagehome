<?php

use App\Support\AppVersion;

test('semantic versions are compared numerically', function () {
    expect(AppVersion::compare('1.10.0', '1.2.0'))->toBe(1)
        ->and(AppVersion::isLessThan('1.2.0', '1.10.0'))->toBeTrue();
});

test('versions missing patch are normalized', function () {
    expect(AppVersion::normalize('1.2'))->toBe('1.2.0')
        ->and(AppVersion::compare('1.2', '1.2.0'))->toBe(0);
});

test('missing or invalid versions are ignored', function () {
    expect(AppVersion::normalize(null))->toBeNull()
        ->and(AppVersion::normalize('invalid'))->toBeNull()
        ->and(AppVersion::compare('invalid', '1.0.0'))->toBeNull();
});
