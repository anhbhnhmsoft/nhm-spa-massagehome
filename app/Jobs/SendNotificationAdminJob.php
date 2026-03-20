<?php

namespace App\Jobs;

use App\Enums\NotificationAdminType;
use App\Enums\QueueKey;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(
        protected NotificationAdminType $type,
        protected array $data = [],
    ) {
        $this->onQueue(QueueKey::NOTIFICATIONS);
    }

    /**
     * @param NotificationService $notificationService
     * @return void
     */
    public function handle(NotificationService $notificationService): void
    {
        $notificationService->sendAdminNotification(
            type: $this->type,
            data: $this->data
        );
    }
}
