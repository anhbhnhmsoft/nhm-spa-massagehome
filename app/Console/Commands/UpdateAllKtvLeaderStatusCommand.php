<?php

namespace App\Console\Commands;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateAllKtvLeaderStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-all-ktv-leader-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và cập nhật trạng thái is_leader cho tất cả KTV dựa trên số lượng KTV họ đã giới thiệu';

    public function __construct(
        protected UserRepository $userRepository,
        protected UserReviewApplicationRepository $reviewApplicationRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Kiểm tra và cập nhật trạng thái KTV trưởng...');

        try {
            $minReferrals = Helper::getConditionToBeLeaderKtv();
            $this->info("Số lượng KTV tối thiểu để lên trưởng nhóm: {$minReferrals}");

            // Lấy tất cả KTV có giới thiệu người khác
            $ktvReferrers = $this->userRepository->query()
                ->where('role', UserRole::KTV->value)
                ->where('is_active', true)
                ->whereHas('reviewApplication', function ($query) {
                    $query->where('role', UserRole::KTV->value);
                })
                ->get();

            $this->info("Tìm thấy {$ktvReferrers->count()} KTV để kiểm tra.");

            $updatedCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            foreach ($ktvReferrers as $referrer) {
                // Đếm số KTV đã được duyệt mà KTV này giới thiệu
                $invitedKtvCount = $this->reviewApplicationRepository->query()
                    ->join('users', 'user_review_application.user_id', '=', 'users.id')
                    ->where('user_review_application.referrer_id', $referrer->id)
                    ->where('user_review_application.status', ReviewApplicationStatus::APPROVED->value)
                    ->where('user_review_application.role', UserRole::KTV->value)
                    ->where('users.role', UserRole::KTV->value)
                    ->whereNull('users.deleted_at')
                    ->whereNull('user_review_application.deleted_at')
                    ->distinct('user_review_application.id')
                    ->count('user_review_application.id');

                // Lấy hồ sơ apply KTV của người giới thiệu
                $reviewApplication = $this->reviewApplicationRepository->query()
                    ->where('user_id', $referrer->id)
                    ->where('role', UserRole::KTV->value)
                    ->first();

                if (!$reviewApplication) {
                    $skippedCount++;
                    continue;
                }

                // Kiểm tra điều kiện và cập nhật
                if ($invitedKtvCount >= $minReferrals) {
                    if (!$reviewApplication->is_leader) {
                        $reviewApplication->is_leader = true;
                        $reviewApplication->save();
                        $updatedCount++;
                        $this->line("✓ KTV {$referrer->name} (ID: {$referrer->id}) đã được cập nhật thành trưởng nhóm ({$invitedKtvCount} KTV được giới thiệu)");
                    }
                }
            }

            DB::commit();

            $this->info("Hoàn thành! Đã cập nhật {$updatedCount} KTV, bỏ qua {$skippedCount} KTV.");

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            DB::rollBack();
            LogHelper::error(
                message: 'Lỗi UpdateAllKtvLeaderStatusCommand@handle',
                ex: $exception,
            );
            $this->error("Lỗi: {$exception->getMessage()}");
            return Command::FAILURE;
        }
    }
}

