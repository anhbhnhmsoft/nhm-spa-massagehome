<?php

namespace App\Enums\Jobs;

enum WalletTransBookingCase
{
    case CONFIRM_BOOKING ; // Phục vụ việc confirm check booking mới

    case FINISH_BOOKING ; // Phục vụ việc hoàn thành check booking
}
