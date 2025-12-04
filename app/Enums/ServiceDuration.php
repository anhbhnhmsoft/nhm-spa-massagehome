<?php

namespace App\Enums;


enum ServiceDuration: int
{
    case FIFTEEN_MINUTE = 15;
    case HALF_HOUR = 30;
    case ONE_HOUR = 60;
    case ONE_AND_HALF_HOUR = 90;
    case TWO_HOUR = 120;
    case TWO_AND_HALF_HOUR = 150;
    case THREE_HOUR = 180;
    case FOUR_HOUR = 240;
}
