<?php

namespace App\Enums;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kiểu thời gian hiển thị dashboard
 */
enum DateRangeDashboard: string
{
    case DAY = 'day'; // Ngày
    case WEEK = 'week'; // Tuần
    case MONTH = 'month'; // Tháng
    case QUARTER = 'quarter'; // Quý
    case YEAR = 'year'; // Năm

    case ALL = 'all'; // Tất cả

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Lấy khoảng thời gian hiển thị
     * @return array{from: Carbon, to: Carbon}
     */
    public function getDateRange(): array
    {
        $now = now();

        $fromDate = match ($this) {
            self::DAY => $now->copy()->startOfDay(),
            self::WEEK => $now->copy()->subDays(6)->startOfDay(), // 6 ngày trước + hôm nay = 7 ngày
            self::MONTH => $now->copy()->startOfMonth(),
            self::QUARTER => $now->copy()->subMonths(3)->startOfDay(),
            self::YEAR => $now->copy()->startOfYear(),
            self::ALL => Carbon::parse('2020-01-01')->startOfDay(),
        };

        return [
            'from' => $fromDate,
            'to' => $now->endOfDay(),
        ];
    }
}
