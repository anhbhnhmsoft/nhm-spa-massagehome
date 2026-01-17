<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\Language;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\NotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis as RedisFacade;

class NotificationService extends BaseService
{
    public function __construct(
        protected NotificationRepository $notificationRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách notifications của user với phân trang
     */
    public function getNotifications(FilterDTO $dto): ServiceReturn
    {
        try {
            $user = Auth::user();
            $query = $this->notificationRepository->queryNotification();

            // Lọc theo user_id
            $dto->addFilter('user_id', $user->id);

            $query = $this->notificationRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->notificationRepository->sortQuery(
                query: $query,
                sortBy: $dto->sortBy,
                direction: $dto->direction
            );

            $paginate = $query->paginate(
                perPage: $dto->perPage,
                page: $dto->page
            );

            return ServiceReturn::success(data: $paginate);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@getNotifications",
                ex: $exception
            );
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $dto->perPage,
                    currentPage: $dto->page
                )
            );
        }
    }

    /**
     * Lấy chi tiết notification
     */
    public function getNotificationDetail(int|string $notificationId): ServiceReturn
    {
        try {
            $user = Auth::user();
            $notification = $this->notificationRepository->queryNotification()
                ->where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                throw new ServiceException(
                    message: __("error.notification_not_found")
                );
            }

            return ServiceReturn::success(data: $notification);
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@getNotificationDetail",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Đánh dấu notification là đã đọc
     */
    public function markAsRead(int|string $notificationId): ServiceReturn
    {
        try {
            $user = Auth::user();
            $notification = $this->notificationRepository->queryNotification()
                ->where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                throw new ServiceException(
                    message: __("error.notification_not_found")
                );
            }

            $notification->markAsRead();

            return ServiceReturn::success(data: $notification);
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@markAsRead",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Lấy số lượng notifications chưa đọc
     */
    public function getUnreadCount(): ServiceReturn
    {
        try {
            $user = Auth::user();
            $count = $this->notificationRepository->queryNotification()
                ->where('user_id', $user->id)
                ->where('status', '!=', NotificationStatus::READ->value)
                ->count();

            return ServiceReturn::success(data: ['count' => $count]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@getUnreadCount",
                ex: $exception
            );
            return ServiceReturn::success(data: ['count' => 0]);
        }
    }

    /**
     * Gửi notification cho user
     */
    public function sendNotification(
        int|string $userId,
        NotificationType $type,
        array $data = []
    ): void {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // Lấy ngôn ngữ của user
        $userLang = Language::tryFrom($user->language) ?? Language::VIETNAMESE;


        // Lấy title và description theo ngôn ngữ của user
        $title = $type->getTitleByLang($userLang);
        $description = $type->getDescByLang($userLang);

        // Tạo notification trong database
        $notification = $this->notificationRepository->query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'description' => $description,
            'type' => $type->value,
            'status' => NotificationStatus::PENDING->value,
            'data' => $data,
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
                'data' => array_merge($data, [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'type' => $type->value,
                ]),
            ];

            // Gửi thông báo qua Redis pub/sub để Node server xử lý
            $redis = RedisFacade::connection('pubsub');
            $channel = config('services.node_server.channel_notification');
            $redis->publish($channel, json_encode($payload));

            // Đánh dấu đã gửi
            $notification->markAsSent();
        } catch (\Throwable $e) {
            // Đánh dấu gửi thất bại
            $notification->markAsFailed();
            LogHelper::error(
                message: "Lỗi NotificationService@sendNotification",
                ex: $e
            );
            throw $e;
        }
    }
}

