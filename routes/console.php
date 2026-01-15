<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckOvertimeBookingJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cronjob kiểm tra booking quá hạn - chạy mỗi 5 phút
Schedule::job(new CheckOvertimeBookingJob())
    ->everyFiveMinutes()
    ->name('check-overtime-bookings')
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('app:refresh-zalo-token-oa-command')
    ->everyThirtyMinutes()
    ->name('refresh-zalo-token-oa')
    ->withoutOverlapping()
    ->onOneServer();

// Cronjob cập nhật trạng thái KTV trưởng - chạy mỗi ngày lúc 2:00 AM
Schedule::command('app:update-all-ktv-leader-status')
    ->daily()
    ->name('update-all-ktv-leader-status')
    ->withoutOverlapping()
    ->onOneServer();
