<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
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
        $result = $this->notificationService->getNotifications($dto);
        
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        
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
        $result = $this->notificationService->getNotificationDetail($id);
        
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
        $result = $this->notificationService->markAsRead($id);
        
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
    public function unreadCount(): JsonResponse
    {
        $result = $this->notificationService->getUnreadCount();
        
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

