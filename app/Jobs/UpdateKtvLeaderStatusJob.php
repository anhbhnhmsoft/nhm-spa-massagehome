<?php

namespace App\Jobs;

use App\Core\LogHelper;
use App\Services\UserService;
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
     * Cập nhật trạng thái is_leader cho KTV
     */
    public function handle(UserService $userService): void
    {
        try {
            $result = $userService->updateKtvLeaderStatus($this->referrerId);

            if ($result->isError()) {
                LogHelper::error(
                    message: 'Lỗi UpdateKtvLeaderStatusJob@handle',
                    ex: new \Exception($result->getMessage()),
                );
            }
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi UpdateKtvLeaderStatusJob@handle',
                ex: $exception,
            );
        }
    }
}


