<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\NotificationStatus;
use App\Http\Resources\Notification\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(
        protected NotificationService $notificationService,
    )
    {
    }

    /**
     * Lấy danh sách notifications của user
     */
    public function list(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $user = $request->user();
        // Lọc theo user_id
        $dto->addFilter('user_id', $user->id);
        // Lọc theo nhiều status (chỉ lấy noti hợp để hiển thị)
        $dto->addFilter('statuses', [NotificationStatus::SENT->value, NotificationStatus::READ->value]);

        $result = $this->notificationService->getMobileNotificationPagination($dto);

        $data = $result->getData();
        return $this->sendSuccess(
            data: NotificationResource::collection($data)->response()->getData(),
        );
    }

    /**
     * Lấy chi tiết notification
     */
    public function detail(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        // Lọc theo user_id
        $result = $this->notificationService->getMobileNotificationDetail(
            notificationId: $id,
            userId: $user->id,
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: new NotificationResource($result->getData()),
        );
    }

    /**
     * Đánh dấu notification là đã đọc
     */
    public function markAsRead(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->notificationService->markAsReadNotificationMobile(
            notificationId: $id,
            userId: $user->id,
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: new NotificationResource($result->getData()),
            message: __('notification.marked_as_read'),
        );
    }

    /**
     * Lấy số lượng notifications chưa đọc
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->notificationService->getUnreadCountNotificationMobile(
            userId: $user->id,
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
        );
    }
}

