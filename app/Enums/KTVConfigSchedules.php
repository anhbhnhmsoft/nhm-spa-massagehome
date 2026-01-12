<?php

namespace App\Enums;

enum KTVConfigSchedules: int
{
    case MONDAY = 2;
    case TUESDAY = 3;
    case WEDNESDAY = 4;
    case THURSDAY = 5;
    case FRIDAY = 6;
    case SATURDAY = 7;
    case SUNDAY = 8;

    public static function getDefaultSchema(): array
    {
        $schema = [];
        foreach (self::cases() as $case) {
            $schema[] = [
                'day_key'   => $case->value,
                'start_time'     => '08:00',
                'end_time'       => '17:00',
                'active'    => true,
            ];
        }
        return $schema;
    }

}
