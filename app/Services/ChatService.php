<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use App\Core\Controller\FilterDTO;
use App\Core\Cache\Caching;
use App\Core\Cache\CacheKey;
use App\Enums\NotificationType;
use App\Jobs\SendNotificationJob;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis as RedisFacade;

class ChatService extends BaseService
{
    public function __construct(
        protected ChatRoomRepository $chatRoomRepository,
        protected MessageRepository $messageRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy hoặc tạo phòng chat giữa customer và KTV
     */
    public function getOrCreateRoom(int|string $customerId, int|string $ktvId): ServiceReturn
    {
        try {
            $query = $this->chatRoomRepository->queryRoom();
            $room = $query
                ->where(function ($q) use ($customerId, $ktvId) {
                    $q->where('customer_id', $customerId)
                        ->where('ktv_id', $ktvId);
                })
                ->orWhere(function ($q) use ($customerId, $ktvId) {
                    $q->where('customer_id', $ktvId)
                        ->where('ktv_id', $customerId);
                })
                ->first();

            if (! $room) {
                $room = $this->chatRoomRepository->create([
                    'customer_id' => $customerId,
                    'ktv_id' => $ktvId,
                ]);
            }

            return ServiceReturn::success(data: $room);
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi ChatService@getOrCreateRoom',
                ex: $exception
            );

            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }

    /**
     * Lấy danh sách messages theo room_id
     */
    public function getMessages(FilterDTO $dto): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            $roomId = $dto->filters['room_id'] ?? null;
            if (! $roomId) {
                return ServiceReturn::success(data: new LengthAwarePaginator([], 0, $dto->perPage, $dto->page));
            }

            $room = $this->chatRoomRepository->find($roomId);
            if (! $room) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            if ((string) $room->customer_id !== (string) $user->id && (string) $room->ktv_id !== (string) $user->id) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            $query = $this->messageRepository->queryByRoom($roomId);
            $query = $this->messageRepository->filterQuery($query, $dto->filters);
            $query = $this->messageRepository->sortQuery($query, $dto->sortBy, $dto->direction);

            $paginate = $query->paginate(perPage: $dto->perPage, page: $dto->page);

            return ServiceReturn::success(data: $paginate);
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi ChatService@getMessages',
                ex: $exception
            );

            return ServiceReturn::success(
                data: new LengthAwarePaginator([], 0, $dto->perPage, $dto->page)
            );
        }
    }

    /**
     * Lưu tin nhắn vào DB và bắn qua Socket.IO theo room_id
     */
    public function sendMessageToRoom(int|string $roomId, string $text): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            // Đảm bảo phòng tồn tại
            $room = $this->chatRoomRepository->find($roomId);
            if (! $room) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            // Chỉ cho phép người tham gia gửi (customer hoặc ktv)
            if ((string) $room->customer_id !== (string) $user->id && (string) $room->ktv_id !== (string) $user->id) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            $message = $this->messageRepository->create([
                'room_id' => $room->id,
                'sender_by' => $user->id,
                'content' => $text,
            ]);

            // Nếu người nhận offline (không heartbeat) thì bắn push notification
            $receiverId = (string) $room->customer_id === (string) $user->id
                ? (string) $room->ktv_id
                : (string) $room->customer_id;

            $isReceiverOnline = Caching::hasCache(
                key: CacheKey::CACHE_USER_HEARTBEAT,
                uniqueKey: $receiverId
            );

            if (! $isReceiverOnline) {
                SendNotificationJob::dispatch(
                    userId: $receiverId,
                    type: NotificationType::CHAT_MESSAGE,
                    data: [
                        'room_id' => (string) $room->id,
                        'message_id' => (string) $message->id,
                        'sender_id' => (string) $user->id,
                        'content' => $message->content,
                    ]
                );
            }

            // Publish tới Socket server
            $redis = RedisFacade::connection('pubsub');
            $channel = env('REDIS_CHANNEL_CHAT', 'chat_messages');

            $payload = [
                'type' => 'message:new',
                'payload' => [
                    'id' => (string) $message->id,
                    'conversationId' => (string) $room->id,
                    'text' => $message->content,
                    'userId' => (string) $user->id,
                    'createdAt' => $message->created_at?->toISOString(),
                ],
            ];

            $redis->publish($channel, json_encode($payload));

            return ServiceReturn::success(
                data: $message,
                message: __('common.success.data_created')
            );
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi ChatService@sendMessageToRoom',
                ex: $exception
            );

            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }

    /**
     * Lấy token của user hiện tại để dùng cho Socket.IO auth
     */
    public function getAuthToken(): ServiceReturn
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            // Tạo token tạm thời (hoặc dùng Sanctum token nếu có)
            $token = $user->createToken('chat-token')->plainTextToken;

            return ServiceReturn::success(
                data: [
                    'token' => $token,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]
            );
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi ChatService@getAuthToken',
                ex: $exception
            );
            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }
}

