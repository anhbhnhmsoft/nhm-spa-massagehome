<?php

namespace App\Console\Commands;

use App\Enums\SupportTicketEventType;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeLegacySupportTickets extends Command
{
    protected $signature = 'support:normalize-legacy-tickets {--apply : Persist the normalization instead of previewing it}';

    protected $description = 'Normalize legacy assigned support tickets without an owner back to the pending queue';

    public function handle(): int
    {
        $tickets = SupportTicket::query()
            ->where('status', SupportTicketStatus::ASSIGNED->dbValue())
            ->whereNull('assigned_staff_id')
            ->orderBy('id')
            ->get(['id']);

        $ids = $tickets->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->info(sprintf('%s legacy ticket(s) found.', count($ids)));
        if ($ids) {
            $this->line('IDs: ' . implode(', ', $ids));
        }

        if (!$this->option('apply') || !$ids) {
            $this->comment($ids ? 'Dry-run only. Re-run with --apply to persist.' : 'Nothing to normalize.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids): void {
            SupportTicket::query()->whereKey($ids)->update([
                'status' => SupportTicketStatus::PENDING->dbValue(),
                'assigned_at' => null,
            ]);

            foreach ($ids as $id) {
                SupportTicketEvent::create([
                    'support_ticket_id' => $id,
                    'event_type' => SupportTicketEventType::REASSIGNED->value,
                    'metadata' => ['reason' => 'legacy_normalization'],
                ]);
            }
        });

        $this->info('Normalized ' . count($ids) . ' ticket(s) to pending.');
        return self::SUCCESS;
    }
}
