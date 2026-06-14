<?php

namespace App\Support;

use App\Enums\ConfigName;
use App\Services\ConfigService;

class MobileVersionFlow
{
    private static ?array $cachedCreateMinVersions = null;
    private static ?array $forcedCreateMinVersions = null;

    public static function forgetCachedVersions(): void
    {
        self::$cachedCreateMinVersions = null;
    }

    public static function forceCreateMinVersions(?array $versions = null): void
    {
        self::$forcedCreateMinVersions = $versions;
        self::forgetCachedVersions();
    }

    public static function shouldUseBookingApplicationCreateFlow(?string $platform, ?string $version): bool
    {
        return self::matchesMinVersion(
            platform: $platform,
            version: $version,
            minimumVersions: self::bookingApplicationCreateMinVersions(),
        );
    }

    public static function shouldUseBookingApplicationCancelFlow(?string $platform, ?string $version): bool
    {
        return self::matchesMinVersion(
            platform: $platform,
            version: $version,
            minimumVersions: self::bookingApplicationCreateMinVersions(),
        );
    }

    public static function shouldUseModernMobileContract(?string $platform, ?string $version): bool
    {
        return self::matchesMinVersion(
            platform: $platform,
            version: $version,
            minimumVersions: self::bookingApplicationCreateMinVersions(),
        );
    }

    public static function bookingApplicationCreateMinVersions(): array
    {
        if (self::$cachedCreateMinVersions !== null) {
            return self::$cachedCreateMinVersions;
        }

        if (self::$forcedCreateMinVersions !== null) {
            self::$cachedCreateMinVersions = self::$forcedCreateMinVersions;
            return self::$cachedCreateMinVersions;
        }

        try {
            /** @var ConfigService $configService */
            $configService = app(ConfigService::class);

            self::$cachedCreateMinVersions = [
                'ios' => AppVersion::normalize(
                    (string) ($configService->getConfigValue(ConfigName::IOS_LATEST_VERSION)
                        ?: config('services.application_mobile.ios_version'))
                ),
                'android' => AppVersion::normalize(
                    (string) ($configService->getConfigValue(ConfigName::ANDROID_LATEST_VERSION)
                        ?: config('services.application_mobile.android_version'))
                ),
            ];
        } catch (\Throwable) {
            self::$cachedCreateMinVersions = [
                'ios' => null,
                'android' => null,
            ];
        }

        return self::$cachedCreateMinVersions;
    }

    private static function matchesMinVersion(?string $platform, ?string $version, array $minimumVersions): bool
    {
        $normalizedPlatform = strtolower((string) $platform);
        $minVersion = $minimumVersions[$normalizedPlatform] ?? null;

        if ($minVersion === null || $version === null) {
            return false;
        }

        $comparison = AppVersion::compare($version, $minVersion);

        return $comparison !== null && $comparison >= 0;
    }
}
