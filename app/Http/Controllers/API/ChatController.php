<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
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
            'user_id' => ['required', 'numeric', 'exists:users,id'],
        ],[
            'user_id.required' => __('validation.user_id.required'),
            'user_id.numeric' => __('validation.user_id.numeric'),
            'user_id.exists' => __('validation.user_id.exists'),
        ]);

        $result = $this->chatService->getOrCreateRoom(
            userId: $data['user_id'],
        );
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }
        $room = $result->getData()['room'];
        $partner = $result->getData()['partner'];
        return $this->sendSuccess(
            data: new ChatRoomResource($room, $partner),
        );
    }

    /**
     * Lấy danh sách tin nhắn theo room_id
     */
    public function listMessages(ListRequest $request, string $roomId): JsonResponse
    {
        $dto = $request->getFilterOptions();

        $result = $this->chatService->messagePagination($dto, $roomId);

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        $data = $result->getData();

        return $this->sendSuccess(
            data: MessageResource::collection($data)->response()->getData(),
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
            'temp_id' => ['nullable', 'string'], // temp_id để client track message khi gửi lỗi
        ],[
            'room_id.required' => __('validation.room_id.required'),
            'room_id.string' => __('validation.room_id.string'),
            'room_id.exists' => __('validation.room_id.exists'),
            'content.required' => __('validation.content.required'),
            'content.string' => __('validation.content.string'),
            'content.max' => __('validation.content.max', ['max' => 2000]),
        ]);

        $result = $this->chatService->sendMessageToRoom(
            roomId: $data['room_id'],
            text: $data['content'],
            tempId: $data['temp_id'] ?? null,
        );

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            message: __('common.success.data_created'),
        );
    }
}


