<?php

namespace App\Listeners;

use App\Enums\Admin\AdminRole;
use App\Events\ProposalRespondedEvent;
use App\Events\ServiceRequestCreatedEvent;
use App\Events\ServiceRequestProposedEvent;
use App\Models\AdminUser;
use App\Services\NotificationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendServiceRequestNotificationListener
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Xử lý Yêu cầu dịch vụ mới từ Khách hàng
     */
    public function handleRequestCreated(ServiceRequestCreatedEvent $event): void
    {
        $request = $event->serviceRequest;
        $customerName = $request->customer?->name ?? 'Khách hàng';

        // Bắn thông báo đến Filament Admin Panel cho CSKH
        $adminUsers = AdminUser::whereIn('role', [
            AdminRole::ADMIN->value,
            AdminRole::CUSTOMER_SUPPORT->value,
        ])->get();

        if ($adminUsers->isNotEmpty()) {
            Notification::make()
                ->title("Yêu cầu dịch vụ mới (#{$request->id})")
                ->body("Khách hàng {$customerName} vừa gửi yêu cầu hỗ trợ matching dịch vụ.")
                ->info()
                ->sendToDatabase($adminUsers, isEventDispatched: true);
        }
    }

    /**
     * Xử lý Đề xuất KTV từ CSKH
     */
    public function handleRequestProposed(ServiceRequestProposedEvent $event): void
    {
        $proposal = $event->proposal;
        $ktvId = $proposal->ktv_id;

        // Bắn thông báo đến KTV
        try {
            $this->notificationService->sendMobileNotification(
                userId: (int)$ktvId,
                type: \App\Enums\NotificationType::NOTIFICATION_MARKETING, // Hoặc loại phù hợp
                data: [
                    'title' => 'Đề xuất dịch vụ mới từ CSKH',
                    'body' => "Bạn nhận được lời mời đề xuất cho Yêu cầu #{$proposal->request_id}. Vui lòng phản hồi.",
                    'request_id' => $proposal->request_id,
                    'proposal_id' => $proposal->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Lỗi bắn thông báo cho KTV trong SendServiceRequestNotificationListener: ' . $e->getMessage());
        }
    }

    /**
     * Xử lý Phản hồi lượt Đề xuất (Từ KTV hoặc Khách hàng)
     */
    public function handleProposalResponded(ProposalRespondedEvent $event): void
    {
        $proposal = $event->proposal;
        $actorRole = $event->actorRole;
        $isAccepted = $event->isAccepted;

        if ($actorRole === 'ktv') {
            if ($isAccepted) {
                // KTV Đồng ý -> Bắn thông báo cho Khách hàng
                try {
                    $this->notificationService->sendMobileNotification(
                        userId: (int)$proposal->serviceRequest->customer_id,
                        type: \App\Enums\NotificationType::NOTIFICATION_MARKETING,
                        data: [
                            'title' => 'KTV đã nhận lời mời',
                            'body' => "KTV {$proposal->ktv?->name} đã sẵn sàng phục vụ. Vui lòng xác nhận chốt đơn!",
                            'proposal_id' => $proposal->id,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('Lỗi bắn thông báo cho Khách hàng: ' . $e->getMessage());
                }
            } else {
                // KTV Từ chối -> Bắn thông báo cho CSKH
                $adminUsers = AdminUser::whereIn('role', [
                    AdminRole::ADMIN->value,
                    AdminRole::CUSTOMER_SUPPORT->value,
                ])->get();

                if ($adminUsers->isNotEmpty()) {
                    Notification::make()
                        ->title("KTV Từ chối đề xuất (#{$proposal->id})")
                        ->body("KTV {$proposal->ktv?->name} vừa từ chối đề xuất cho Yêu cầu #{$proposal->request_id}.")
                        ->warning()
                        ->sendToDatabase($adminUsers, isEventDispatched: true);
                }
            }
        } elseif ($actorRole === 'customer') {
            if ($isAccepted) {
                // Khách đồng ý -> Thông báo thành công cho KTV & CSKH
                try {
                    $this->notificationService->sendMobileNotification(
                        userId: (int)$proposal->ktv_id,
                        type: \App\Enums\NotificationType::NOTIFICATION_MARKETING,
                        data: [
                            'title' => 'Chốt Booking thành công!',
                            'body' => 'Khách hàng đã chấp nhận đề xuất và Booking đã được khởi tạo tự động.',
                            'proposal_id' => $proposal->id,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('Lỗi bắn thông báo cho KTV: ' . $e->getMessage());
                }
            }
        }
    }
}
