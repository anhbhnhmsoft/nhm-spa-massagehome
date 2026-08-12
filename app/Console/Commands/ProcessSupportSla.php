<?php

namespace App\Console\Commands;

use App\Services\SupportService;
use Illuminate\Console\Command;

class ProcessSupportSla extends Command
{
    protected $signature = 'support:process-sla';

    protected $description = 'Record support SLA warnings and return breached tickets to the queue';

    public function handle(SupportService $supportService): int
    {
        $result = $supportService->processSla();
        $this->info(sprintf(
            'SLA processed: %d warning(s), %d breach(es).',
            count($result['warning_ids'] ?? []),
            count($result['breached_ids'] ?? []),
        ));

        return self::SUCCESS;
    }
}
