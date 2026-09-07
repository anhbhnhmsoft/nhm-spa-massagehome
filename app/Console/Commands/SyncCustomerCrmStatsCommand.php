<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\CustomerCrmData;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncCustomerCrmStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:sync-stats {--user_id= : Chỉ định đồng bộ cho 1 user ID cụ thể}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ lại toàn bộ dữ liệu lịch sử đơn hoàn thành, tổng chi tiêu và AOV cho khách hàng vào bảng CRM';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificUserId = $this->option('user_id');

        $query = User::query()->where('role', UserRole::CUSTOMER->value);

        if ($specificUserId) {
            $query->where('id', $specificUserId);
        }

        $totalUsers = $query->count();
        $this->info("Bắt đầu đồng bộ chỉ số CRM cho {$totalUsers} khách hàng...");

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        $updatedCount = 0;

        $query->chunk(100, function ($users) use ($bar, &$updatedCount) {
            foreach ($users as $user) {
                $completedBookings = ServiceBooking::where('user_id', (string) $user->id)
                    ->where('status', BookingStatus::COMPLETED->value);

                $totalSpent = (float) $completedBookings->sum('price');
                $bookingCount = $completedBookings->count();
                $aov = $bookingCount > 0 ? $totalSpent / $bookingCount : 0.0;

                $firstBookingAt = ServiceBooking::where('user_id', (string) $user->id)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->orderBy('created_at', 'asc')
                    ->value('created_at');

                $lastBookingAt = ServiceBooking::where('user_id', (string) $user->id)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->orderBy('created_at', 'desc')
                    ->value('created_at');

                $frequentBookingHours = ServiceBooking::where('user_id', (string) $user->id)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->pluck('created_at')
                    ->map(fn ($time) => Carbon::parse($time)->format('H:00'))
                    ->toArray();

                CustomerCrmData::updateOrCreate(
                    ['user_id' => (string) $user->id],
                    [
                        'total_spent' => $totalSpent,
                        'booking_count' => $bookingCount,
                        'aov' => $aov,
                        'first_booking_at' => $firstBookingAt,
                        'last_booking_at' => $lastBookingAt,
                        'frequent_booking_hours' => array_values(array_unique($frequentBookingHours)),
                    ]
                );

                $updatedCount++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Đã đồng bộ thành công dữ liệu CRM cho {$updatedCount} khách hàng!");

        return Command::SUCCESS;
    }
}
