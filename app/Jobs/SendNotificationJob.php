<?php

namespace App\Jobs;

use App\Enums\Language;
use App\Enums\NotificationDescription;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis as RedisFacade;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int|string $userId,
        protected NotificationType $type,
        protected array $data = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        // Lấy ngôn ngữ của user
        $userLang = Language::tryFrom($user->language) ?? Language::VIETNAMESE;

        $notificationDesc = NotificationDescription::fromNotificationType($this->type);

        // Lấy title và description theo của user
        $title = $notificationDesc->getTitleByLang($userLang);
        $description = $notificationDesc->getDescByLang($userLang);

        // Tạo notification trong database
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'description' => $description,
            'type' => $this->type->value,
            'status' => NotificationStatus::PENDING->value,
            'data' => $this->data,
        ]);

        try {
            // Lấy device tokens của user để gửi push notification
            $tokens = $user->routeNotificationForExpo();
            
            // Nếu user không có device tokens thì không gửi push notification
            if (empty($tokens)) {
                // Vẫn đánh dấu đã gửi vì đã lưu vào database
                $notification->markAsSent();
                return;
            }

            // Gửi thông báo qua Redis channel để Node server xử lý
            $payload = [
                'tokens' => $tokens,
                'title' => $title,
                'body' => $description,
                'data' => array_merge($this->data, [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'type' => $this->type->value,
                ]),
            ];

            // Gửi thông báo qua Redis pub/sub để Node server xử lý
            $redis = RedisFacade::connection();
            $channel = 'expo_notifications';
            $redis->publish($channel, json_encode($payload));

            // Đánh dấu đã gửi
            $notification->markAsSent();
        } catch (\Throwable $e) {
            // Đánh dấu gửi thất bại
            $notification->markAsFailed();
            throw $e;
        }
    }
}

