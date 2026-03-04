<?php

namespace App\Console\Commands;

use App\Services\AuthService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DeleteExpiredOtpRecord extends Command
{
    protected $signature = 'app:delete-expired-otp-record';

    protected $description = 'Xóa các OTP đã hết hạn';

    public function __construct(
        protected AuthService $authService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Xóa OTP đã hết hạn
        $this->authService->deleteExpiredOtpRecord();

        return CommandAlias::SUCCESS;
    }
}
