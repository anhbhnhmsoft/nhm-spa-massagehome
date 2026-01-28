<?php

namespace App\Console\Commands;

use App\Services\Facades\BookingFacadeService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckBookingCommand extends Command
{
    protected $signature = 'app:check-booking';

    protected $description = 'Kiểm tra các booking quá hạn';

    public function __construct(
        protected BookingFacadeService $bookingFacadeService,
    ) {
        parent::__construct();
    }


    public function handle(): int
    {
        // Kiểm tra các booking quá hạn
        $this->bookingFacadeService->checkOverdueBookings();

        return CommandAlias::SUCCESS;
    }
}
