<?php
// Define mock enum first
namespace App\Enums {
    if (!class_exists('App\Enums\NotificationType') && !enum_exists('App\Enums\NotificationType')) {
        enum NotificationType: string
        {
            case SYSTEM_MAINTENANCE = 'SYSTEM_MAINTENANCE';
            case DISCOUNT = 'DISCOUNT';
            case DANGER_SUPPORT = 'DANGER_SUPPORT';
            case PAYMENT_COMPLETE = 'PAYMENT_COMPLETE';
            case BOOKING_CONFIRMED = 'BOOKING_CONFIRMED';
            case BOOKING_CANCELLED = 'BOOKING_CANCELLED';
            case BOOKING_REMINDER = 'BOOKING_REMINDER';
            case WALLET_DEPOSIT = 'WALLET_DEPOSIT';
            case WALLET_WITHDRAW = 'WALLET_WITHDRAW';
            case CHAT_MESSAGE = 'CHAT_MESSAGE';
            case TECHNICIAN_WALLET_NOT_ENOUGH = 'TECHNICIAN_WALLET_NOT_ENOUGH';
            case STAFF_APPLY_SUCCESS = 'STAFF_APPLY_SUCCESS';
            case STAFF_APPLY_REJECTED = 'STAFF_APPLY_REJECTED';
            case BOOKING_REFUNDED = 'BOOKING_REFUNDED';
            case BOOKING_COMPLETED = 'BOOKING_COMPLETED';
            case BOOKING_SUCCESS = 'BOOKING_SUCCESS';
            case NEW_BOOKING_REQUEST = 'NEW_BOOKING_REQUEST';
            case BOOKING_AUTO_FINISHED = 'BOOKING_AUTO_FINISHED';
            case BOOKING_OVERTIME_WARNING = 'BOOKING_OVERTIME_WARNING';
            case BOOKING_START = 'BOOKING_START';
            case WALLET_TRANSACTION_CANCELLED = 'WALLET_TRANSACTION_CANCELLED';
            case PAYMENT_SERVICE_FOR_TECHNICIAN = 'PAYMENT_SERVICE_FOR_TECHNICIAN';
            case DEPOSIT_SUCCESS = 'DEPOSIT_SUCCESS';
            case DEPOSIT_FAILED = 'DEPOSIT_FAILED';
        }
    }
}

namespace {
    /**
     * Script to check missing translation keys between Vietnamese and other languages
     */

    // Define stub functions for Laravel helpers
    if (!function_exists('asset')) {
        function asset($path)
        {
            return $path;
        }
    }

    if (!function_exists('__')) {
        function __($key)
        {
            return $key;
        }
    }

    function extractKeysFromArray($array, $prefix = '')
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $keys = array_merge($keys, extractKeysFromArray($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }
        return $keys;
    }

    function loadTranslationFile($path)
    {
        if (!file_exists($path)) {
            return [];
        }
        return include $path;
    }

    $basePath = __DIR__ . '/lang';
    // $languages = ['vi', 'cn', 'en'];

    // Get all translation files from Vietnamese (reference)
    $viFiles = glob($basePath . '/vi/*.php');
    $report = [];

    foreach ($viFiles as $viFile) {
        $filename = basename($viFile);
        $viData = loadTranslationFile($viFile);
        $viKeys = extractKeysFromArray($viData);

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "Checking file: {$filename}\n";
        echo str_repeat('=', 80) . "\n";

        foreach (['cn', 'en'] as $lang) {
            $langFile = $basePath . '/' . $lang . '/' . $filename;

            if (!file_exists($langFile)) {
                echo "\n❌ FILE MISSING in {$lang}: {$filename}\n";
                $report[$filename][$lang]['missing_file'] = true;
                $report[$filename][$lang]['missing_keys'] = $viKeys;
                continue;
            }

            $langData = loadTranslationFile($langFile);
            $langKeys = extractKeysFromArray($langData);

            $missingKeys = array_diff($viKeys, $langKeys);
            $extraKeys = array_diff($langKeys, $viKeys);

            if (empty($missingKeys) && empty($extraKeys)) {
                echo "\n✅ {$lang}: All keys match!\n";
            } else {
                if (!empty($missingKeys)) {
                    echo "\n⚠️  {$lang}: Missing " . count($missingKeys) . " keys:\n";
                    foreach ($missingKeys as $key) {
                        echo "   - {$key}\n";
                    }
                    $report[$filename][$lang]['missing_keys'] = $missingKeys;
                }

                if (!empty($extraKeys)) {
                    echo "\n📝 {$lang}: Extra " . count($extraKeys) . " keys (not in vi):\n";
                    foreach ($extraKeys as $key) {
                        echo "   - {$key}\n";
                    }
                    $report[$filename][$lang]['extra_keys'] = $extraKeys;
                }
            }
        }
    }

    // Summary
    echo "\n\n" . str_repeat('=', 80) . "\n";
    echo "SUMMARY\n";
    echo str_repeat('=', 80) . "\n";

    $totalMissingCn = 0;
    $totalMissingEn = 0;

    foreach ($report as $file => $langs) {
        foreach ($langs as $lang => $data) {
            if (isset($data['missing_keys'])) {
                $count = count($data['missing_keys']);
                if ($lang === 'cn') $totalMissingCn += $count;
                if ($lang === 'en') $totalMissingEn += $count;
            }
        }
    }

    echo "\nTotal missing keys in CN: {$totalMissingCn}\n";
    echo "Total missing keys in EN: {$totalMissingEn}\n";

    if ($totalMissingCn === 0 && $totalMissingEn === 0) {
        echo "\n✅ All translations are complete!\n";
    } else {
        echo "\n⚠️  Translation keys need to be added.\n";
    }
}
