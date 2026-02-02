<?php

namespace App\Console\Commands;

use App\Core\LogHelper;
use App\Enums\ConfigName;
use App\Services\ConfigService;
use App\Services\UserService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

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
        protected UserService $userService,
        protected ConfigService $configService,
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
            $minReferrals = $this->configService->getConfigValue(ConfigName::KTV_LEADER_MIN_REFERRALS);
            $this->info("Số lượng KTV tối thiểu để lên trưởng nhóm: {$minReferrals}");

            $result = $this->userService->updateAllKtvLeaderStatus($minReferrals);

            if ($result->isError()) {
                $this->error("Lỗi: {$result->getMessage()}");
                return CommandAlias::FAILURE;
            }

            $data = $result->getData();
            $this->info("Tìm thấy {$data['total_checked']} KTV để kiểm tra.");
            $this->info("Hoàn thành! Đã cập nhật {$data['updated_count']} KTV, bỏ qua {$data['skipped_count']} KTV.");

            return CommandAlias::SUCCESS;
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi UpdateAllKtvLeaderStatusCommand@handle',
                ex: $exception,
            );
            $this->error("Lỗi: {$exception->getMessage()}");
            return CommandAlias::FAILURE;
        }
    }
}

