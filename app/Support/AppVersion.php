<?php

namespace App\Support;

class AppVersion
{
    public static function normalize(?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        $version = trim($version);

        if (! preg_match('/^\d+(?:\.\d+){0,2}$/', $version)) {
            return null;
        }

        $parts = array_map('intval', explode('.', $version));

        while (count($parts) < 3) {
            $parts[] = 0;
        }

        return implode('.', $parts);
    }

    public static function compare(?string $left, ?string $right): ?int
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if ($left === null || $right === null) {
            return null;
        }

        $leftParts = array_map('intval', explode('.', $left));
        $rightParts = array_map('intval', explode('.', $right));

        return $leftParts <=> $rightParts;
    }

    public static function isLessThan(?string $left, ?string $right): bool
    {
        return self::compare($left, $right) === -1;
    }
}
