<?php

use Illuminate\Support\Facades\Schedule;


// Cronjob kiểm tra booking quá hạn - chạy mỗi 5 phút
Schedule::command('app:check-booking')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Cronjob cập nhật token Zalo OA - chạy mỗi 30 phút
Schedule::command('app:refresh-zalo-token-oa-command')
    ->everyThirtyMinutes()
    ->name('refresh-zalo-token-oa')
    ->withoutOverlapping()
    ->onOneServer();

// Cronjob cập nhật trạng thái KTV trưởng - chạy mỗi ngày lúc 2:00 AM
Schedule::command('app:update-all-ktv-leader-status')
    ->dailyAt('02:00')
    ->name('update-all-ktv-leader-status')
    ->withoutOverlapping()
    ->onOneServer();
