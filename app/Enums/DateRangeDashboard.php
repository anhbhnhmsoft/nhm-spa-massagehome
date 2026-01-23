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

    public function label(): string
    {
        return match ($this) {
            self::DAY => __('admin.date_range.day'),
            self::WEEK => __('admin.date_range.week'),
            self::MONTH => __('admin.date_range.month'),
            self::QUARTER => __('admin.date_range.quarter'),
            self::YEAR => __('admin.date_range.year'),
            self::ALL => __('admin.date_range.all'),
        };
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
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

    /**
     * Trả về cấu hình gom nhóm dựa trên khoảng thời gian chọn
     */
    public function getGroupingConfig(): array
    {
        return match ($this) {
            self::DAY => [
                'unit' => 'hour',
                'format' => 'H:00',
                'pg_format' => 'HH24:00', // Định dạng giờ 24h cho PostgreSQL
            ],
            self::WEEK, self::MONTH => [
                'unit' => 'day',
                'format' => 'd/m',
                'pg_format' => 'YYYY-MM-DD',
            ],
            self::QUARTER => [
                'unit' => 'week',
                'format' => 'd/m',
                'pg_format' => 'YYYY-MM-DD', // Ngày đầu tuần (Thứ 2)
            ],
            self::YEAR => [
                'unit' => 'month',
                'format' => 'm/Y',
                'pg_format' => 'YYYY-MM',
            ],
            self::ALL => [
                'unit' => 'year',
                'format' => 'Y',
                'pg_format' => 'YYYY',
            ],
        };
    }
}
