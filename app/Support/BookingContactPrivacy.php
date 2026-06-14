<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\ServiceBooking;
use App\Models\User;

class BookingContactPrivacy
{
    public static function canViewCustomerContact(ServiceBooking $booking, ?User $viewer): bool
    {
        if (!$viewer || $viewer->role !== UserRole::KTV->value) {
            return true;
        }

        if (!in_array((int) $booking->status, [
            BookingStatus::CONFIRMED->value,
            BookingStatus::ONGOING->value,
            BookingStatus::COMPLETED->value,
        ], true)) {
            return false;
        }

        return (string) $booking->ktv_user_id === (string) $viewer->id;
    }

    public static function maskCustomerName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return self::maskWord($name);
        }

        return implode(' ', array_map(fn (string $part) => self::maskWord($part), $parts));
    }

    public static function maskAddress(?string $address): ?string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $address))));
        if (count($segments) >= 2) {
            return implode(', ', array_slice($segments, -2));
        }

        $length = mb_strlen($address);
        if ($length <= 8) {
            return self::maskWord($address);
        }

        return '*** ' . mb_substr($address, max(0, $length - 8));
    }

    private static function maskWord(string $value): string
    {
        $length = mb_strlen($value);
        if ($length <= 1) {
            return '*';
        }

        if ($length === 2) {
            return mb_substr($value, 0, 1) . '*';
        }

        return mb_substr($value, 0, 1) . str_repeat('*', max(1, $length - 2)) . mb_substr($value, -1);
    }
}
