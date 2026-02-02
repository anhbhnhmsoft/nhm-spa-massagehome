<?php

namespace App\Enums\Jobs;

enum WalletTransCase
{
    case CONFIRM_BOOKING ; // Phục vụ việc confirm check booking mới

    case FINISH_BOOKING ; // Phục vụ việc hoàn thành check booking

    case REWARD_FOR_KTV_REFERRAL; // Phục vụ việc trả tiền giới thiệu cho KTV
}
