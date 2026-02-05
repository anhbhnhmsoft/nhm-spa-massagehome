<?php

namespace App\Enums\Jobs;

enum WalletTransCase
{
    case CONFIRM_BOOKING ; // Phục vụ việc confirm check booking mới

    case FINISH_BOOKING ; // Phục vụ việc hoàn thành check booking

    case CONFIRM_CANCEL_BOOKING; // Phục vụ việc hủy check booking

    case REWARD_FOR_KTV_REFERRAL; // Phục vụ việc trả tiền giới thiệu cho KTV

    case CREATE_WITHDRAW_REQUEST; // Phục vụ việc tạo thông tin rút tiền

    case CONFIRM_WITHDRAW_REQUEST; // Phục vụ việc xác nhận rút tiền

    case CANCEL_WITHDRAW_REQUEST; // Phục vụ việc hủy rút tiền
}
