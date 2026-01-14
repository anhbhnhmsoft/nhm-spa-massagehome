<?php

namespace App\Console\Commands;

use App\Services\ZaloService;
use Illuminate\Console\Command;
use Throwable;

class RefreshZaloTokenOACommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-zalo-token-oa-command';

    protected $tries = 3;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(ZaloService $zaloService)
    {
        $result = $zaloService->getAccessTokenForOA();

        if ($result) {
            $this->info('Refresh token OA success');
        } else {
            throw new \Exception('Refresh token OA failed');
        }
    }

    public function fail(Throwable|string|null $exception = null)
    {
        $this->error('Refresh token OA failed: ' . $exception->getMessage());
    }
}
