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
    protected $signature = 'app:clear-ktv-working-status {--hours=24 : So gio toi thieu ke tu lan cap nhat schedule cuoi cung}';

    protected $description = 'Tu dong doi is_working ve true cho cac KTV dang bi ket false, khong con booking ongoing, va schedule da cu hon nguong quy dinh';

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

        $this->info("Bat dau reconcile is_working cho KTV. Threshold: {$hours} gio.");

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

            $scheduleUpdatedAt = Carbon::make($schedule->updated_at);
            if (!$scheduleUpdatedAt || $scheduleUpdatedAt->greaterThan($cutoffTime)) {
                $skippedCount++;
                $this->line("Skip KTV {$ktvId}: schedule moi cap nhat gan day ({$schedule->updated_at}).");
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
