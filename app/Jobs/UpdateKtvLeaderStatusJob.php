<?php

namespace App\Jobs;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateKtvLeaderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $referrerId;

    public function __construct(int $referrerId)
    {
        $this->referrerId = $referrerId;
    }

    /**
     * - Đếm số KTV đã được phê duyệt mà KTV này giới thiệu
     */
    public function handle(
        UserRepository $userRepository,
        UserReviewApplicationRepository $reviewApplicationRepository,
    ): void {
        try {
            $referrer = $userRepository->query()
                ->where('id', $this->referrerId)
                ->where('role', UserRole::KTV->value)
                ->first();

            if (!$referrer) {
                return;
            }

            // Đếm số KTV đã được duyệt mà KTV này giới thiệu
            $invitedKtvCount = $reviewApplicationRepository->query()
                ->where('referrer_id', $referrer->id)
                ->where('status', ReviewApplicationStatus::APPROVED->value)
                ->whereHas('user', function ($query) {
                    $query->where('role', UserRole::KTV->value);
                })
                ->count();

            if ($invitedKtvCount < Helper::getConditionToBeLeaderKtv()) {
                return;
            }

            // Lấy hồ sơ apply KTV của người giới thiệu
            $reviewApplication = $reviewApplicationRepository->query()
                ->where('user_id', $referrer->id)
                ->where('role', UserRole::KTV->value)
                ->first();

            if (!$reviewApplication) {
                return;
            }

            // Đánh dấu là trưởng nhóm KTV
            if (!$reviewApplication->is_leader) {
                $reviewApplication->is_leader = true;
                $reviewApplication->save();
            }
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi UpdateKtvLeaderStatusJob@handle',
                ex: $exception,
            );
        }
    }
}


