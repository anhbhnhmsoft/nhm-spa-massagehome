<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\NodeServerConstant;
use App\Enums\UserRole;
use App\Http\Resources\Chat\MessageResource;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use App\Core\Controller\FilterDTO;
use App\Core\Cache\Caching;
use App\Core\Cache\CacheKey;
use App\Enums\NotificationType;
use App\Jobs\SendNotificationJob;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis as RedisFacade;

class ChatService extends BaseService
{
    public function __construct(
        protected ChatRoomRepository $chatRoomRepository,
        protected MessageRepository $messageRepository,
        protected UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy hoặc tạo phòng chat giữa customer và KTV
     * @param int $userId
     * @return ServiceReturn
     */
    public function getOrCreateRoom(int $userId): ServiceReturn
    {
        try {
            // Check Auth & Token
            if (!Auth::check()) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }
            $currentUser = Auth::user();
            $token = request()->bearerToken();
            if (!$token) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }
            $isCurrentUserKtv = $currentUser->role == UserRole::KTV->value;

            // Query tìm đối phương
            $partnerQuery = $this->userRepository->queryUser();
            // Nếu là KTV, tìm customer theo ID. Nếu là Customer, tìm KTV
            if ($isCurrentUserKtv) {
                $partnerQuery->whereNot('role', UserRole::KTV->value);
            }else{
                $partnerQuery->where('role', UserRole::KTV->value);
            }
            // Tìm đối phương
            $partner = $partnerQuery->where('id', $userId)->first();
            if (!$partner) {
                throw new ServiceException(message: __('error.user_not_found'));
            }
            // Gán ID cho đúng cột trong bảng chat_rooms
            $ktvId = $isCurrentUserKtv ? $currentUser->id : $partner->id;
            $customerId = $isCurrentUserKtv ? $partner->id : $currentUser->id;

            // 3. Tìm hoặc tạo phòng
            $room = $this->chatRoomRepository->query()
                ->where('ktv_id', $ktvId)
                ->where('customer_id', $customerId)
                ->first();

            if (!$room) {
                $room = $this->chatRoomRepository->query()->create([
                    'ktv_id' => $ktvId,
                    'customer_id' => $customerId,
                ]);
            }
            // Lưu token vào Redis
            $key = config('services.node_server.channel_chat_auth') . ":{$token}";
            $redisPayload = [
                'id' => (string) $currentUser->id,
                'name' =>  $currentUser->name,
                'room_id' => (string) $room->id,
            ];
            RedisFacade::connection()->setex(
                $key,              // Key
                60 * 60 * 2,       // Thời gian hết hạn (giây) - TTL
                json_encode($redisPayload) // Value
            );
            return ServiceReturn::success(data: [
                'room' => $room,
                'partner' => $partner,
            ]);
        } catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
        catch (\Throwable $exception) {
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
    public function messagePagination(FilterDTO $dto, string $roomId): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }
            if (!$roomId) {
                return ServiceReturn::success(data: new LengthAwarePaginator([], 0, $dto->perPage, $dto->page));
            }
            // Kiểm tra quyền truy cập vào phòng chat
            $room = $this->chatRoomRepository->find($roomId);
            if (!$room) {
                throw new ServiceException(message: __('common_error.data_not_found'));
            }

            if ((string) $room->customer_id !== (string) $user->id && (string) $room->ktv_id !== (string) $user->id) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }

            $query = $this->messageRepository->queryByRoom($roomId);
            $query = $this->messageRepository->filterQuery($query, $dto->filters);
            $query = $this->messageRepository->sortQuery($query, $dto->sortBy, $dto->direction);

            $paginate = $query->paginate(perPage: $dto->perPage, page: $dto->page);

            return ServiceReturn::success(data: $paginate);
        }catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
        catch (\Throwable $exception) {
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
     * @param int $roomId ID phòng chat
     * @param string $text Nội dung tin nhắn
     * @param string|null $tempId ID tạm thời (nếu có)
     * @return ServiceReturn
     */
    public function sendMessageToRoom(
        int $roomId,
        string $text,
        ?string $tempId = null
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }

            // Đảm bảo phòng tồn tại
            $room = $this->chatRoomRepository->find($roomId);
            if (!$room) {
                throw new ServiceException(message: __('common_error.data_not_found'));
            }

            // Chỉ cho phép người tham gia gửi (customer hoặc ktv)
            if ((string) $room->customer_id !== (string) $user->id
                && (string) $room->ktv_id !== (string) $user->id) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }

            // Lưu tin nhắn vào DB
            $message = $this->messageRepository->create([
                'room_id' => $room->id,
                'sender_by' => $user->id,
                'content' => $text,
                'temp_id' => $tempId ?? null,
            ]);

            // Cập nhật thời gian tin nhắn mới nhất trong phòng chat
            $room->touch();

            // commit ở đây để đảm bảo tin nhắn được lưu trước khi bắn qua Socket
            DB::commit();

            // Nếu người nhận offline (không heartbeat) thì bắn push notification
            $receiverId = (string) $room->customer_id === (string) $user->id
                ? (string) $room->ktv_id
                : (string) $room->customer_id;
            $isReceiverOnline = Caching::hasCache(
                key: CacheKey::CACHE_USER_HEARTBEAT,
                uniqueKey: $receiverId
            );

            // Nếu người nhận không online thì bắn push notification
            if (!$isReceiverOnline) {
                SendNotificationJob::dispatch(
                    userId: $receiverId,
                    type: NotificationType::CHAT_MESSAGE,
                    data: [
                        'room_id' => (string) $room->id,
                        'message_id' => (string) $message->id,
                        'sender_id' => (string) $user->id,
                        'sender_name' => $user->name, // Thêm tên để hiển thị trên Noti
                        'content' => $message->content,
                    ]
                );
            }

            // Publish tới Socket server
            RedisFacade::connection()->publish(
                config('services.node_server.channel_chat'),
                json_encode([
                    'type' => NodeServerConstant::CHAT_MESSAGE_NEW,
                    'payload' => new MessageResource($message),
                ])
            );

            return ServiceReturn::success(
                data: $message,
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(message: $exception->getMessage());
        }
        catch (\Throwable $exception) {
            DB::commit();
            LogHelper::error(
                message: 'Lỗi ChatService@sendMessageToRoom',
                ex: $exception
            );
            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }

}

