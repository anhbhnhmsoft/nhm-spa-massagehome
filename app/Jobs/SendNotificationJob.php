<?php

namespace App\Jobs;

use App\Enums\QueueKey;
use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int|string $userId,
        protected NotificationType $type,
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
        $notificationService->sendMobileNotification(
            userId: $this->userId,
            type: $this->type,
            data: $this->data
        );
    }
}

