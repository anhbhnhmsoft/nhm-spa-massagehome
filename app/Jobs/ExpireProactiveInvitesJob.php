<?php

namespace App\Jobs;

use App\Enums\InvitationStatus;
use App\Models\KtvProactiveInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireProactiveInvitesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {
            $affected = KtvProactiveInvite::where('status', InvitationStatus::PENDING)
                ->where('expires_at', '<=', now())
                ->update(['status' => InvitationStatus::EXPIRED->value]);

            if ($affected > 0) {
                Log::info("ExpireProactiveInvitesJob: Đã tự động hết hạn {$affected} lời mời Proactive KTV.");
            }
        } catch (\Throwable $e) {
            Log::error("Lỗi trong ExpireProactiveInvitesJob: " . $e->getMessage());
        }
    }
}
