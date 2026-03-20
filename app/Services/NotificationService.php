<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\Admin\AdminRole;
use App\Enums\Language;
use App\Enums\NotificationAdminType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Enums\RecieverNotification;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\WalletTransactionResource;
use App\Repositories\AdminUserRepository;
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
        protected AdminUserRepository    $adminUserRepository,
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
            $title = $type->getTitle($userLang, $data);
            $description = $type->getBody($userLang, $data);

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

            // Gửi thông báo cho admin
            $adUsers = $this->adminUserRepository->queryAdminUser()
                ->where('role', AdminRole::ADMIN)
                ->get();
            if (!$adUsers->isEmpty()){
                $adUsers->each(function ($adUser) use ($type,  $data, $user){
                    $adUserLang = $adUser->language ?? Language::VIETNAMESE;
                    $title = $type->getTitle($adUserLang, $data);
                    $description = $type->getBody($adUserLang, $data);
                    $roleUser = match ($user->role){
                        UserRole::KTV->value => "KTV",
                        UserRole::AGENCY->value => "Agency",
                        default => "User",
                    };
                    Notification::make()
                        ->title($title)
                        ->body($description . " - " . $roleUser . ":" . $user->id)
                        ->actions([
                            Action::make(__('notification.marked_as_read'))
                                ->button()
                                ->color('secondary')
                                ->markAsRead()
                        ])
                        ->sendToDatabase($adUser, isEventDispatched: true);
                });
            }

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
    public function sendAdminNotification(NotificationAdminType $type, array $data = []): ServiceReturn
    {
        return $this->execute(
            function () use ($type, $data) {
                $targetRoles = $type->getTargetRoles();

                $adUser = $this->adminUserRepository->queryAdminUser()
                    ->whereIn('role', $targetRoles)
                    ->get();
                if ($adUser->isEmpty()){
                    return ServiceReturn::success();
                }
                $notification = $this->buildNotificationAdmin($type, $data);
                if ($notification){
                    $notification
                        ->sendToDatabase($adUser, isEventDispatched: true);
                }
                return ServiceReturn::success();
            }
        );

    }

    /**
     * Gửi notification global cho user
     * @param array $data
     * @param RecieverNotification $receiver
     * @return ServiceReturn
     */
    public function sendGlobalNotification(array $data, RecieverNotification $receiver): ServiceReturn
    {
        try {
            $query = $this->userRepository->queryUser();
            switch ($receiver) {
                case RecieverNotification::CLIENT:
                    $query->where('role', UserRole::CUSTOMER->value);
                    break;
                case RecieverNotification::KTV:
                    $query->where('role', UserRole::KTV->value);
                    break;
                case RecieverNotification::AGENCY:
                    $query->where('role', UserRole::AGENCY->value);
                    break;
                case RecieverNotification::ALL:
                default:
                    break;
            }

            $query->chunk(100, function ($users) use ($data) {
                foreach ($users as $user) {
                    try {
                        $this->sendMobileNotification(
                            userId: $user->id,
                            type: NotificationType::NOTIFICATION_MARKETING,
                            data: $data
                        );
                    } catch (\Throwable $e) {
                        LogHelper::error(
                            message: "Lỗi gửi notification cho user {$user->id}",
                            ex: $e
                        );
                    }
                }
            });

            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi NotificationService@sendGlobalNotification",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }





    /**
     *  ---- PRIVATE METHODS ----
     */

    private function buildNotificationAdmin(NotificationAdminType $type, array $data = [])
    {
        $detailAction = Action::make(__('notification.detail'))
            ->button()
            ->color('primary')
            ->markAsRead();
        $readAction = Action::make(__('notification.marked_as_read'))
            ->button()
            ->color('secondary')
            ->markAsRead();
        $notification = Notification::make();
        return match ($type) {
            // Thông báo booking quá hạn
            NotificationAdminType::OVERDUE_ONGOING_BOOKING => $notification
                ->title(__('notification.overdue_ongoing_booking.title'))
                ->warning()
                ->body(__('notification.overdue_ongoing_booking.body', $data))
                ->actions([
                    $detailAction->url(BookingResource::getUrl('view', ['record' => $data['booking_id']])),
                    $readAction,
                ]),

            NotificationAdminType::OVERDUE_CONFIRMED_BOOKING => $notification
                ->title(__('notification.overdue_confirmed_booking.title'))
                ->warning()
                ->body(__('notification.overdue_confirmed_booking.body', $data))
                ->actions([
                    $detailAction->url(BookingResource::getUrl('view', ['record' => $data['booking_id']])),
                    $readAction,
                ]),

            NotificationAdminType::USER_APPLY_KTV_PARTNER => $notification
                ->title(__('notification.user_apply_ktv_partner.title'))
                ->info()
                ->body(__('notification.user_apply_ktv_partner.body', $data))
                ->actions([
                    $detailAction->url(KTVResource::getUrl('edit', ['record' => $data['user_id']])),
                    $readAction,
                ]),

            NotificationAdminType::USER_APPLY_AGENCY_PARTNER => $notification
                ->title(__('notification.user_apply_agency_partner.title'))
                ->info()
                ->body(__('notification.user_apply_agency_partner.body', $data))
                ->actions([
                    $detailAction->url(AgencyResource::getUrl('edit', ['record' => $data['user_id']])),
                    $readAction,
                ]),

            NotificationAdminType::CONFIRM_WECHAT_PAYMENT=> $notification // Gộp 2 cái giống nhau
            ->title(__("notification.confirm_wechat_payment.title")) // Giả sử value của enum khớp với key
            ->info()
                ->body(__("notification.confirm_wechat_payment.body", $data))
                ->actions([
                    $detailAction->url(WalletTransactionResource::getUrl('index', ['search' => $data['transaction_id']])),
                    $readAction,
                ]),
            NotificationAdminType::CONFIRM_ALIPAY_PAYMENT => $notification
            ->title(__("notification.confirm_alipay_payment.title"))
            ->info()
                ->body(__("notification.confirm_alipay_payment.body", $data))
                ->actions([
                    $detailAction->url(WalletTransactionResource::getUrl('index', ['search' => $data['transaction_id']])),
                    $readAction,
                ]),
            NotificationAdminType::EMERGENCY_SUPPORT => $notification
                ->title(__('notification.emergency_support.title'))
                ->danger()
                ->body(__('notification.emergency_support.body', ['booking_id' => $data['booking_id'] ?? "Unknown"]))
                ->actions([
                    $detailAction->url(isset($data['booking_id']) ? BookingResource::getUrl('view', ['record' => $data['booking_id']]) : null),
                    $readAction,
                ]),
            default => null,
        };
    }
}
