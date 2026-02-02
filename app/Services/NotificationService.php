<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\Language;
use App\Enums\NotificationAdminType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis as RedisFacade;
use function PHPUnit\Framework\matches;

class NotificationService extends BaseService
{
    public function __construct(
        protected NotificationRepository $notificationRepository,
        protected UserRepository         $userRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách notifications mobile của user với phân trang
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function getMobileNotificationPagination(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->notificationRepository->queryNotification();

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
                message: "Lỗi NotificationService@getMobileNotificationPagination",
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
     * Lấy chi tiết notification mobile
     * @param int|string $notificationId
     * @param int|string $userId
     * @return ServiceReturn
     */
    public function getMobileNotificationDetail($notificationId, $userId): ServiceReturn
    {
        try {
            $notification = $this->notificationRepository->queryNotification()
                ->where('id', $notificationId)
                ->where('user_id', $userId)
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
                message: "Lỗi NotificationService@getMobileNotificationDetail",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Đánh dấu notification mobile là đã đọc
     * @param int|string $notificationId
     * @param int|string $userId
     * @return ServiceReturn
     */
    public function markAsReadNotificationMobile($notificationId, $userId): ServiceReturn
    {
        try {
            $notification = $this->notificationRepository->queryNotification()
                ->where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();

            if (!$notification) {
                throw new ServiceException(
                    message: __("error.notification_not_found")
                );
            }

            $this->notificationRepository->setStatus($notificationId, NotificationStatus::READ);

            return ServiceReturn::success(data: $notification);
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@markAsReadNotificationMobile",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Lấy số lượng notifications mobile chưa đọc
     */
    public function getUnreadCountNotificationMobile($userId): ServiceReturn
    {
        try {
            $count = $this->notificationRepository->queryNotification()
                ->where('user_id', $userId)
                ->where('status', NotificationStatus::SENT->value)
                ->count();

            return ServiceReturn::success(data: ['count' => $count]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@getUnreadCountNotificationMobile",
                ex: $exception
            );
            return ServiceReturn::success(data: ['count' => 0]);
        }
    }

    /**
     * Gửi notification mobile cho user
     * @param int|string $userId
     * @param NotificationType $type
     * @param array $data
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function sendMobileNotification(
        int|string       $userId,
        NotificationType $type,
        array            $data = []
    )
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->query()->find($userId);
            if (!$user) {
                throw new ServiceException(
                    message: __("error.user_not_found")
                );
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

            // Lấy device tokens của user để gửi push notification
            $tokens = $user->routeNotificationForExpo();

            // Nếu user không có device tokens thì không gửi push notification
            if (empty($tokens)) {
                // Vẫn đánh dấu đã gửi vì đã lưu vào database
                $this->notificationRepository->setStatus($notification->id, NotificationStatus::SENT);
                DB::commit();
                return ServiceReturn::success();
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
            $redis = RedisFacade::connection();
            $channel = config('services.node_server.channel_notification');
            $redis->publish($channel, json_encode($payload));

            // Đánh dấu đã gửi
            $this->notificationRepository->setStatus($notification->id, NotificationStatus::SENT);

            DB::commit();
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi NotificationService@sendMobileNotification",
                ex: $e
            );
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Gửi thông báo cho admin
     * @param NotificationAdminType $type
     * @param array $data
     * @return ServiceReturn
     */
    public function sendAdminNotification(NotificationAdminType $type, array $data = [])
    {
        try {
            $admins = $this->userRepository->queryUser()
                ->where('role', UserRole::ADMIN->value)
                ->get();
            $admins->each(function ($admin) use ($type, $data) {
                switch ($type) {
                    // Thông báo booking quá hạn
                    case NotificationAdminType::OVERDUE_ONGOING_BOOKING:
                        Notification::make()
                            ->title(__('notification.overdue_ongoing_booking.title'))
                            ->warning()
                            ->body(__('notification.overdue_ongoing_booking.body', [
                                'booking_id' => $data['booking_id'],
                                'start_time' => $data['start_time'],
                                'duration' => $data['duration'],
                            ]))
                            ->actions([
                                Action::make(__('notification.detail'))
                                    ->button()
                                    ->color('primary')
                                    ->url(BookingResource::getUrl('view', ['record' => $data['booking_id']]))
                                    ->markAsRead(),
                                Action::make(__('notification.marked_as_read'))
                                    ->button()
                                    ->markAsRead(),

                            ])
                            ->sendToDatabase($admin);
                        break;
                    // Thông báo booking quá hạn
                    case NotificationAdminType::OVERDUE_CONFIRMED_BOOKING:
                        Notification::make()
                            ->title(__('notification.overdue_confirmed_booking.title'))
                            ->warning()
                            ->body(__('notification.overdue_confirmed_booking.body', [
                                'booking_id' => $data['booking_id'],
                                'booking_time' => $data['booking_time'],
                                'duration' => $data['duration'],
                            ]))
                            ->actions([
                                Action::make(__('notification.detail'))
                                    ->button()
                                    ->color('primary')
                                    ->url(BookingResource::getUrl('view', ['record' => $data['booking_id']]))
                                    ->markAsRead(),
                                Action::make(__('notification.marked_as_read'))
                                    ->button()
                                    ->markAsRead(),

                            ])
                            ->sendToDatabase($admin);
                        break;
                }
            });
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@sendAdminNotification",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }
}

