<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Repositories\BookingRepository;
use App\Repositories\UserKtvScheduleRepository;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ClearKtvWorkingStatusCommand extends Command
{
    protected $signature = 'app:clear-ktv-working-status {--hours=24 : So gio lookback de tim booking da ket thuc}';

    protected $description = 'Tu dong doi is_working ve true cho cac KTV dang bi ket false nhung khong con booking ongoing';

    public function __construct(
        protected UserRepository $userRepository,
        protected UserKtvScheduleRepository $userKtvScheduleRepository,
        protected BookingRepository $bookingRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hours = max((int) $this->option('hours'), 1);
        $cutoffTime = Carbon::now()->subHours($hours);

        $this->info("Bat dau reconcile is_working cho KTV. Lookback: {$hours} gio.");

        $stuckSchedules = $this->userKtvScheduleRepository->query()
            ->with('technician')
            ->where('is_working', false)
            ->whereHas('technician', function ($query) {
                $query->where('role', UserRole::KTV->value)
                    ->where('is_active', true);
            })
            ->get();

        if ($stuckSchedules->isEmpty()) {
            $this->info('Khong co KTV nao dang is_working=false de xu ly.');
            return CommandAlias::SUCCESS;
        }

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($stuckSchedules as $schedule) {
            $ktvId = $schedule->ktv_id;

            $hasOngoingBooking = $this->bookingRepository->query()
                ->where('ktv_user_id', $ktvId)
                ->where('status', BookingStatus::ONGOING->value)
                ->exists();

            if ($hasOngoingBooking) {
                $skippedCount++;
                $this->line("Skip KTV {$ktvId}: van con booking ongoing.");
                continue;
            }

            $hasRecentlyFinishedOrCancelledBooking = $this->bookingRepository->query()
                ->where('ktv_user_id', $ktvId)
                ->whereNotNull('start_time')
                ->where(function ($query) use ($cutoffTime) {
                    $query->where(function ($subQuery) use ($cutoffTime) {
                        $subQuery->whereIn('status', [
                            BookingStatus::COMPLETED->value,
                            BookingStatus::CANCELED->value,
                            BookingStatus::PAYMENT_FAILED->value,
                        ])
                            ->where(function ($timeQuery) use ($cutoffTime) {
                                $timeQuery->where('updated_at', '>=', $cutoffTime)
                                    ->orWhere('end_time', '>=', $cutoffTime);
                            });
                    })->orWhere(function ($subQuery) use ($cutoffTime) {
                        $subQuery->where('status', BookingStatus::WAITING_CANCEL->value)
                            ->where('updated_at', '>=', $cutoffTime);
                    });
                })
                ->exists();

            if (!$hasRecentlyFinishedOrCancelledBooking) {
                $skippedCount++;
                $this->line("Skip KTV {$ktvId}: khong co dau hieu booking da ket thuc/cancel gan day.");
                continue;
            }

            $schedule->update([
                'is_working' => true,
            ]);

            $updatedCount++;
            $this->info("Restored is_working=true cho KTV {$ktvId}");
        }

        $this->newLine();
        $this->info("Hoan thanh. Updated: {$updatedCount}, Skipped: {$skippedCount}");

        return CommandAlias::SUCCESS;
    }
}
