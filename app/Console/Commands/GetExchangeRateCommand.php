<?php

namespace App\Console\Commands;

use App\Core\LogHelper;
use App\Services\ConfigService;
use Illuminate\Console\Command;

class GetExchangeRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-exchange-rate-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get exchange rate from API';

    /**
     * Execute the console command.
     */
    public function handle(ConfigService $configService)
    {
        $configService->getExchangeRateVndCny();
    }
}
