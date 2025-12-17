<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Requests\Chat\ListMessagesRequest;
use App\Http\Resources\Chat\ChatRoomResource;
use App\Http\Resources\Chat\MessageResource;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends BaseController
{
    public function __construct(
        protected ChatService $chatService,
    ) {
    }

    /**
     * Tạo hoặc lấy phòng chat giữa customer và KTV
     */
    public function createOrGetRoom(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ktv_id' => ['required', 'string', 'exists:users,id'],
        ]);

        $customerId = $request->user()?->id;
        if (!$customerId) {
            return $this->sendError(message: __('common_error.unauthorized'), code: 401);
        }

        $result = $this->chatService->getOrCreateRoom(
            customerId: $customerId,
            ktvId: $data['ktv_id']
        );

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new ChatRoomResource($result->getData()),
            message: __('common.success.data_created'),
        );
    }

    /**
     * Lấy danh sách tin nhắn theo room_id
     */
    public function listMessages(ListMessagesRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();

        $result = $this->chatService->getMessages($dto);
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        $data = $result->getData();

        return $this->sendSuccess(
            data: MessageResource::collection($data)->response()->getData(),
            message: __('common.success.data_list'),
        );
    }

    /**
     * Gửi tin nhắn trong room (lưu DB + publish realtime).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'string', 'exists:chat_rooms,id'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $result = $this->chatService->sendMessageToRoom(
            roomId: $data['room_id'],
            text: $data['content']
        );

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new MessageResource($result->getData()),
            message: __('common.success.data_created'),
        );
    }
}


