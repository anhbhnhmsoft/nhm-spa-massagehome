<?php

namespace App\Support;

class MobileVersionFlow
{
    private const BOOKING_APPLICATION_CANCEL_MIN_VERSION = [
        'ios' => '1.0.8',
        'android' => '1.0.8',
    ];

    public static function shouldUseBookingApplicationCancelFlow(?string $platform, ?string $version): bool
    {
        $normalizedPlatform = strtolower((string) $platform);
        $minVersion = self::BOOKING_APPLICATION_CANCEL_MIN_VERSION[$normalizedPlatform] ?? null;

        if ($minVersion === null || $version === null) {
            return false;
        }

        $comparison = AppVersion::compare($version, $minVersion);

        return $comparison !== null && $comparison >= 0;
    }
}
