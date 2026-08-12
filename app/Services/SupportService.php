<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\NodeServerConstant;
use App\Enums\SupportMessageSenderType;
use App\Enums\SupportTicketEventType;
use App\Enums\SupportTicketStatus;
use App\Enums\Admin\AdminRole;
use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Repositories\AdminUserRepository;
use App\Repositories\BookingRepository;
use App\Repositories\SupportCategoryRepository;
use App\Repositories\SupportMessageRepository;
use App\Repositories\SupportTicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Str;

class SupportService extends BaseService
{
    public function __construct(
        protected SupportCategoryRepository $supportCategoryRepository,
        protected SupportTicketRepository $supportTicketRepository,
        protected SupportMessageRepository $supportMessageRepository,
        protected AdminUserRepository $adminUserRepository,
        protected UserRepository $userRepository,
        protected BookingRepository $bookingRepository,
        protected NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function listCategories(): ServiceReturn
    {
        try {
            $data = Caching::remember(
                CacheKey::CACHE_KEY_SUPPORT_CATEGORY,
                function () {
                    return $this->supportCategoryRepository->query()
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->get();
                },
                expire: 60
            );

            return ServiceReturn::success(data: $data);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@listCategories', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function createTicketForCustomer(int $customerId, int $categoryId, ?string $content = null): ServiceReturn
    {
        return $this->execute(function () use ($customerId, $categoryId, $content) {
            $customer = $this->userRepository->find($customerId);
            if (!$customer) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            $category = $this->supportCategoryRepository->find($categoryId);
            if (!$category || !$category->is_active) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            $latestBooking = $this->bookingRepository->query()
                ->where('user_id', $customerId)
                ->latest('id')
                ->first();

            $ticket = $this->supportTicketRepository->create([
                'customer_id' => $customerId,
                'category_id' => $categoryId,
                'latest_booking_id' => $latestBooking?->id,
                'status' => SupportTicketStatus::PENDING,
            ]);

            $ticket->room_id = $this->makeRoomId($ticket->id);
            $staff = $this->selectOnlineStaff();
            if ($staff) {
                $ticket->assigned_staff_id = $staff->id;
                $ticket->status = SupportTicketStatus::ASSIGNED;
                $ticket->assigned_at = now();
            }
            $ticket->save();

            $this->recordTicketEvent($ticket, SupportTicketEventType::CREATED, toStaffId: $ticket->assigned_staff_id);
            if ($ticket->assigned_staff_id) {
                $this->recordTicketEvent($ticket, SupportTicketEventType::CLAIMED, toStaffId: $ticket->assigned_staff_id, metadata: ['automatic' => true]);
            }

            $message = null;
            if ($content && trim($content) !== '') {
                $message = $this->supportMessageRepository->create([
                'support_ticket_id' => $ticket->id,
                    'sender_type' => SupportMessageSenderType::CUSTOMER,
                    'sender_user_id' => $customerId,
                    'content' => $content,
                ]);
                $ticket->last_message_at = $message->created_at;
                $ticket->save();
            }

            $ticket->load([
                'customer.profile',
                'assignedStaff',
                'category',
                'latestBooking.user.profile',
                'latestBooking.service',
                'latestMessage.customer',
                'latestMessage.staff',
            ]);

            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_CREATED, [
                'ticket' => $this->serializeTicket($ticket),
                'message' => $message ? $this->serializeMessage($message) : null,
                'broadcast_staff_ids' => $ticket->assigned_staff_id ? [(string) $ticket->assigned_staff_id] : $this->onlineSupportStaffIds(),
            ]);

            return ServiceReturn::success(data: [
                'ticket' => $ticket,
            ]);
        }, useTransaction: true);
    }

    public function initiateChatFromStaff(int $staffId, int $customerId): ServiceReturn
    {
        return $this->execute(function () use ($staffId, $customerId) {
            $customer = $this->userRepository->find($customerId);
            if (!$customer) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            $shouldNotifyCustomer = false;

            // Tìm một ticket đang mở (chưa đóng) giữa nhân viên này và khách hàng này
            $ticket = $this->supportTicketRepository->query()
                ->where('customer_id', $customerId)
                ->where('assigned_staff_id', $staffId)
                ->where('status', '!=', SupportTicketStatus::CLOSED->dbValue())
                ->first();

            if (!$ticket) {
                // Lấy một category mặc định theo dữ liệu tinker)
                $category = $this->supportCategoryRepository->query()->where('position', 5)->first();
                if (!$category) {
                    // Hoặc lấy category đầu tiên nếu không tìm thấy
                    $category = $this->supportCategoryRepository->query()->where('is_active', true)->first();
                }
                if (!$category) {
                    throw new ServiceException('Hệ thống chưa cấu hình danh mục hỗ trợ.');
                }

                $ticket = $this->supportTicketRepository->create([
                    'customer_id' => $customerId,
                    'category_id' => $category->id,
                    'assigned_staff_id' => $staffId,
                    'status' => SupportTicketStatus::ASSIGNED,
                ]);
                $ticket->room_id = $this->makeRoomId($ticket->id);
                $ticket->assigned_at = now();
                $ticket->save();
                $this->recordTicketEvent($ticket, SupportTicketEventType::CREATED, actorAdminId: $staffId, toStaffId: $staffId, metadata: ['initiated_by_staff' => true]);
                $this->recordTicketEvent($ticket, SupportTicketEventType::CLAIMED, actorAdminId: $staffId, toStaffId: $staffId, metadata: ['automatic' => true]);
                $shouldNotifyCustomer = true;
            }

            if ($shouldNotifyCustomer) {
                $this->notificationService->sendMobileNotification(
                    userId: $ticket->customer_id,
                    type: \App\Enums\NotificationType::SUPPORT_CHAT_MESSAGE,
                    data: [
                        'staff_name' => Auth::user()->name,
                        'message_content' => Str::limit('Nhân viên hệ thống liên hệ hỗ trợ', 100),
                        'support_ticket_id' => (string) $ticket->id,
                    ]
                );
            }

            return ServiceReturn::success(data: $ticket);
        });
    }

    public function listCustomerTickets(int $customerId, int $page = 1, int $perPage = 15): ServiceReturn
    {
        try {
            $paginator = $this->supportTicketRepository->queryWithRelations()
                ->where('customer_id', $customerId)
                ->orderByDesc('last_message_at')
                ->paginate(perPage: $perPage, page: $page);

            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@listCustomerTickets', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function listStaffTickets(int $staffId, string $scope = 'all', int $page = 1, int $perPage = 15): ServiceReturn
    {
        try {
            $query = $this->supportTicketRepository->queryWithRelations();
            if ($scope === 'mine') {
                $query->whereIn('status', [SupportTicketStatus::ASSIGNED->dbValue(), SupportTicketStatus::IN_PROGRESS->dbValue()])
                    ->where('assigned_staff_id', $staffId);
            } elseif ($scope === 'pending') {
                $query->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
            } elseif ($scope === 'open') {
                $query->whereIn('status', [
                    SupportTicketStatus::PENDING->dbValue(),
                    SupportTicketStatus::ASSIGNED->dbValue(),
                    SupportTicketStatus::IN_PROGRESS->dbValue(),
                ])->where(function ($q) use ($staffId) {
                    $q->where(function ($pending) {
                        $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                    })->orWhere('assigned_staff_id', $staffId);
                });
            } else {
                // The sale portal must never receive another staff member's ticket.
                $query->whereIn('status', [
                    SupportTicketStatus::PENDING->dbValue(),
                    SupportTicketStatus::ASSIGNED->dbValue(),
                    SupportTicketStatus::IN_PROGRESS->dbValue(),
                ])->where(function ($q) use ($staffId) {
                    $q->where(function ($pending) {
                        $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                    })->orWhere('assigned_staff_id', $staffId);
                });
            }
            $paginator = $query->orderByDesc('last_message_at')->paginate(perPage: $perPage, page: $page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@listStaffTickets', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Return exact queue counters for the staff workspace.
     * The portal previously inferred totals from the first paginated page.
     */
    public function getStaffQueueStats(int $staffId): ServiceReturn
    {
        try {
            $activeStatuses = [
                SupportTicketStatus::PENDING->dbValue(),
                SupportTicketStatus::ASSIGNED->dbValue(),
                SupportTicketStatus::IN_PROGRESS->dbValue(),
            ];

            $base = $this->supportTicketRepository->query();
            $pending = (clone $base)
                ->whereNull('assigned_staff_id')
                ->where('status', SupportTicketStatus::PENDING->dbValue())
                ->count();
            $open = (clone $base)
                ->whereIn('status', $activeStatuses)
                ->where(function ($query) use ($staffId) {
                    $query->where(function ($pending) {
                        $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                    })->orWhere('assigned_staff_id', $staffId);
                })
                ->count();
            $mine = (clone $base)
                ->whereIn('status', $activeStatuses)
                ->where('assigned_staff_id', $staffId)
                ->count();
            $unread = $this->supportMessageRepository->query()
                ->where('sender_type', SupportMessageSenderType::CUSTOMER->dbValue())
                ->whereNull('seen_at')
                ->whereHas('ticket', fn ($query) => $query
                    ->whereIn('status', $activeStatuses)
                    ->where(function ($q) use ($staffId) {
                        $q->where(function ($pending) {
                            $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                        })->orWhere('assigned_staff_id', $staffId);
                    }))
                ->count();

            return ServiceReturn::success(data: [
                'pending' => $pending,
                'open' => $open,
                'mine' => $mine,
                'unread' => $unread,
                'online_staff' => $this->onlineSupportStaffCount(),
                'sla_warning' => (clone $base)->whereIn('status', $activeStatuses)->whereNotNull('sla_warning_at')->whereNull('first_response_at')->where(function ($q) use ($staffId) {
                    $q->where(function ($pending) {
                        $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                    })->orWhere('assigned_staff_id', $staffId);
                })->count(),
                'sla_breached' => (clone $base)->whereIn('status', $activeStatuses)->whereNotNull('sla_breached_at')->where(function ($q) use ($staffId) {
                    $q->where(function ($pending) {
                        $pending->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
                    })->orWhere('assigned_staff_id', $staffId);
                })->count(),
            ]);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@getStaffQueueStats', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function detailTicket(int $ticketId): ServiceReturn
    {
        try {
            $ticket = $this->supportTicketRepository->queryWithRelations()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            return ServiceReturn::success(data: $ticket);
        } catch (ServiceException $exception) {
            return ServiceReturn::error($exception->getMessage());
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@detailTicket', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function listMessages(int $ticketId, int $page = 1, int $perPage = 30): ServiceReturn
    {
        try {
            $ticket = $this->supportTicketRepository->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            $paginator = $this->supportMessageRepository->queryByTicket($ticketId)
                ->orderByDesc('id')
                ->paginate(perPage: $perPage, page: $page);

            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@listMessages', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function sendMessage(int $ticketId, string $content, ?string $tempId = null, ?SupportMessageSenderType $senderType = null): ServiceReturn
    {
        return $this->execute(function () use ($ticketId, $content, $tempId, $senderType) {
            $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ($ticket->status === SupportTicketStatus::CLOSED->dbValue()) {
                throw new ServiceException('Ticket đã đóng, không thể gửi tin nhắn.');
            }

            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $senderType = $senderType ?? ($user instanceof AdminUser ? SupportMessageSenderType::STAFF : SupportMessageSenderType::CUSTOMER);
            if ($user instanceof AdminUser && (string) $ticket->assigned_staff_id !== (string) $user->id) {
                throw new ServiceException(__('common_error.unauthorized'));
            }
            $data = [
                'support_ticket_id' => $ticket->id,
                'sender_type' => $senderType,
                'content' => $content,
                'temp_id' => $tempId,
            ];

            if ($user instanceof AdminUser) {
                $data['sender_admin_id'] = $user->id;
            } else {
                $data['sender_user_id'] = $user->id;
            }

            $message = $this->supportMessageRepository->create($data);

            if (!$ticket->assigned_staff_id && $user instanceof AdminUser) {
                $ticket->assigned_staff_id = $user->id;
                $ticket->status = SupportTicketStatus::ASSIGNED;
                $ticket->assigned_at ??= now();
            } elseif ($user instanceof AdminUser && $ticket->status === SupportTicketStatus::PENDING->dbValue()) {
                $ticket->status = SupportTicketStatus::ASSIGNED;
            }

            if ($user instanceof AdminUser && !$ticket->first_response_at) {
                $ticket->first_response_at = now();
            }

            $ticket->last_message_at = $message->created_at;
            $ticket->save();

            $ticket->load([
                'customer.profile',
                'assignedStaff',
                'category',
                'latestBooking.user.profile',
                'latestBooking.service',
                'latestMessage.customer',
                'latestMessage.staff',
            ]);
            $message->load(['customer.profile', 'staff']);

            $this->publishSupportEvent(NodeServerConstant::SUPPORT_MESSAGE_NEW, [
                'ticket' => $this->serializeTicket($ticket),
                'message' => $this->serializeMessage($message),
            ]);

            if ($user instanceof AdminUser) {
                $isCustomerOnline = Caching::hasCache(
                    key: CacheKey::CACHE_USER_HEARTBEAT,
                    uniqueKey: (string) $ticket->customer_id
                );

                if (!$isCustomerOnline) {
                    $this->notificationService->sendMobileNotification(
                        userId: $ticket->customer_id,
                        type: \App\Enums\NotificationType::SUPPORT_CHAT_MESSAGE,
                        data: [
                            'staff_name' => $user->name,
                            'message_content' => Str::limit($content, 100),
                            'support_ticket_id' => (string) $ticket->id,
                        ]
                    );
                }
            }

            return ServiceReturn::success(data: $message);
        }, useTransaction: true);
    }

    public function seenMessages(int $ticketId): ServiceReturn
    {
        return $this->execute(function () use ($ticketId) {
            $ticket = $this->supportTicketRepository->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $query = $this->supportMessageRepository->query()
                ->where('support_ticket_id', $ticket->id);

            if ($user instanceof AdminUser) {
                $query->where('sender_type', '!=', SupportMessageSenderType::STAFF->dbValue());
            } else {
                $query->where('sender_type', '!=', SupportMessageSenderType::CUSTOMER->dbValue());
            }

            $query->whereNull('seen_at')->update(['seen_at' => now()]);
            return ServiceReturn::success();
        }, useTransaction: true);
    }

    public function claimTicket(int $ticketId, int $staffId): ServiceReturn
    {
        return $this->execute(function () use ($ticketId, $staffId) {
            $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ($ticket->assigned_staff_id || $ticket->status !== SupportTicketStatus::PENDING->dbValue()) {
                throw new ServiceException(__('common_error.unauthorized'), 409);
            }

            $staff = $this->adminUserRepository->find($staffId);
            if (!$staff || !$staff->is_active || $staff->role !== AdminRole::CUSTOMER_SUPPORT) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $ticket->assigned_staff_id = $staffId;
            $ticket->status = SupportTicketStatus::ASSIGNED;
            $ticket->assigned_at = now();
            $ticket->save();

            $message = $this->supportMessageRepository->create([
                'support_ticket_id' => $ticket->id,
                'sender_type' => \App\Enums\SupportMessageSenderType::STAFF,
                'sender_admin_id' => $staff->id,
                'content' => "Xin chào, tôi là {$staff->name}, nhân viên phụ trách sẽ hỗ trợ bạn.",
            ]);
            $ticket->last_message_at = $message->created_at;
            $ticket->save();
            $this->recordTicketEvent($ticket, SupportTicketEventType::CLAIMED, actorAdminId: $staffId, toStaffId: $staffId);

            $ticket->load([
                'customer.profile',
                'assignedStaff',
                'category',
                'latestBooking.user.profile',
                'latestBooking.service',
                'latestMessage.customer',
                'latestMessage.staff',
            ]);
            $message->load(['customer.profile', 'staff']);

            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_CLAIMED, [
                'ticket' => $this->serializeTicket($ticket),
            ]);

            $this->publishSupportEvent(NodeServerConstant::SUPPORT_MESSAGE_NEW, [
                'ticket' => $this->serializeTicket($ticket),
                'message' => $this->serializeMessage($message),
            ]);

            return ServiceReturn::success(data: $ticket);
        }, useTransaction: true);
    }

    public function closeTicket(int $ticketId, int $staffId): ServiceReturn
    {
        return $this->closeTicketWithReason($ticketId, $staffId, 'resolved', null);
    }

    public function closeTicketWithReason(int $ticketId, int $staffId, string $reason, ?string $note = null): ServiceReturn
    {
        return $this->execute(function () use ($ticketId, $staffId, $reason, $note) {
            if (!in_array($reason, ['resolved', 'customer_no_response', 'duplicate', 'out_of_scope'], true)) {
                throw new ServiceException('Lý do đóng ticket không hợp lệ.');
            }
            $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ((string) $ticket->assigned_staff_id !== (string) $staffId) {
                throw new ServiceException(__('common_error.unauthorized'));
            }
            if ($ticket->status === SupportTicketStatus::CLOSED->dbValue()) {
                return ServiceReturn::success(data: $ticket);
            }

            $ticket->status = SupportTicketStatus::CLOSED;
            $ticket->closed_at = now();
            $ticket->closed_by_admin_id = $staffId;
            $ticket->close_reason = $reason;
            $ticket->close_note = $note;
            $ticket->save();
            $this->recordTicketEvent($ticket, SupportTicketEventType::CLOSED, actorAdminId: $staffId, fromStaffId: $staffId, metadata: ['reason' => $reason, 'note' => $note]);

            $ticket->load([
                'customer.profile',
                'assignedStaff',
                'category',
                'latestBooking.user.profile',
                'latestBooking.service',
                'latestMessage.customer',
                'latestMessage.staff',
            ]);
            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_CLOSED, [
                'ticket' => $this->serializeTicket($ticket),
            ]);

            return ServiceReturn::success(data: $ticket);
        }, useTransaction: true);
    }

    /** Administrative close path used by the superadmin monitor; it still records the same audit event. */
    public function adminCloseTicket(int $ticketId, int $adminId, string $reason, ?string $note = null): ServiceReturn
    {
        return $this->execute(function () use ($ticketId, $adminId, $reason, $note) {
            $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ($ticket->status === SupportTicketStatus::CLOSED->dbValue()) {
                return ServiceReturn::success(data: $ticket);
            }
            $ticket->status = SupportTicketStatus::CLOSED;
            $ticket->closed_at = now();
            $ticket->closed_by_admin_id = $adminId;
            $ticket->close_reason = $reason;
            $ticket->close_note = $note;
            $ticket->save();
            $this->recordTicketEvent($ticket, SupportTicketEventType::CLOSED, actorAdminId: $adminId, fromStaffId: $ticket->assigned_staff_id, metadata: ['reason' => $reason, 'administrative' => true]);
            $ticket->load(['customer.profile', 'assignedStaff', 'category', 'latestBooking.service', 'latestMessage.customer', 'latestMessage.staff']);
            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_CLOSED, ['ticket' => $this->serializeTicket($ticket)]);
            return ServiceReturn::success(data: $ticket);
        }, useTransaction: true);
    }

    public function reopenTicket(int $ticketId, int $adminId): ServiceReturn
    {
        return $this->execute(function () use ($ticketId, $adminId) {
            $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            $fromStaffId = $ticket->assigned_staff_id;
            $ticket->status = $fromStaffId ? SupportTicketStatus::ASSIGNED : SupportTicketStatus::PENDING;
            $ticket->closed_at = null;
            $ticket->closed_by_admin_id = null;
            $ticket->close_reason = null;
            $ticket->close_note = null;
            $ticket->sla_warning_at = null;
            $ticket->sla_breached_at = null;
            $ticket->save();
            $this->recordTicketEvent($ticket, SupportTicketEventType::REOPENED, actorAdminId: $adminId, toStaffId: $ticket->assigned_staff_id);
            $ticket->load(['customer.profile', 'assignedStaff', 'category', 'latestBooking.service', 'latestMessage.customer', 'latestMessage.staff']);
            $reopenPayload = [
                'ticket' => $this->serializeTicket($ticket),
                'broadcast_staff_ids' => $ticket->assigned_staff_id ? [$ticket->assigned_staff_id] : $this->onlineSupportStaffIds(),
            ];
            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_REOPENED, $reopenPayload);
            return ServiceReturn::success(data: $ticket);
        }, useTransaction: true);
    }

    /** Process warning and breach thresholds once; safe to call from a one-minute scheduler. */
    public function processSla(): array
    {
        $warningIds = [];
        $breachedIds = [];
        $now = now();

        $this->supportTicketRepository->query()
            ->whereIn('status', [SupportTicketStatus::PENDING->dbValue(), SupportTicketStatus::ASSIGNED->dbValue(), SupportTicketStatus::IN_PROGRESS->dbValue()])
            ->whereNull('first_response_at')
            ->whereNull('sla_warning_at')
            ->where('created_at', '<=', $now->copy()->subMinutes(5))
            ->pluck('id')
            ->each(function ($id) use (&$warningIds) {
                $result = $this->execute(function () use ($id, &$warningIds) {
                    $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($id);
                    if (!$ticket || $ticket->first_response_at || $ticket->sla_warning_at || $ticket->status === SupportTicketStatus::CLOSED->dbValue()) {
                        return ServiceReturn::success();
                    }
                    $ticket->sla_warning_at = now();
                    $ticket->save();
                    $this->recordTicketEvent($ticket, SupportTicketEventType::SLA_WARNING, metadata: ['threshold_minutes' => 5]);
                    $ticket->load(['customer.profile', 'assignedStaff', 'category', 'latestBooking.service', 'latestMessage.customer', 'latestMessage.staff']);
                    $this->notifySlaAdmins($ticket, warning: true);
                    $this->publishSupportEvent(NodeServerConstant::SUPPORT_SLA_WARNING, ['ticket' => $this->serializeTicket($ticket), 'broadcast_staff_ids' => $ticket->assigned_staff_id ? [(string) $ticket->assigned_staff_id] : $this->onlineSupportStaffIds()]);
                    $warningIds[] = (string) $ticket->id;
                    return ServiceReturn::success();
                }, useTransaction: true);
                return $result;
            });

        $this->supportTicketRepository->query()
            ->whereIn('status', [SupportTicketStatus::PENDING->dbValue(), SupportTicketStatus::ASSIGNED->dbValue(), SupportTicketStatus::IN_PROGRESS->dbValue()])
            ->whereNull('first_response_at')
            ->whereNull('sla_breached_at')
            ->where('created_at', '<=', $now->copy()->subMinutes(15))
            ->pluck('id')
            ->each(function ($id) use (&$breachedIds) {
                $this->execute(function () use ($id, &$breachedIds) {
                    $ticket = $this->supportTicketRepository->query()->lockForUpdate()->find($id);
                    if (!$ticket || $ticket->first_response_at || $ticket->sla_breached_at || $ticket->status === SupportTicketStatus::CLOSED->dbValue()) {
                        return ServiceReturn::success();
                    }
                    $fromStaffId = $ticket->assigned_staff_id;
                    $ticket->sla_breached_at = now();
                    $ticket->assigned_staff_id = null;
                    $ticket->assigned_at = null;
                    $ticket->status = SupportTicketStatus::PENDING;
                    $ticket->save();
                    $this->recordTicketEvent($ticket, SupportTicketEventType::SLA_BREACHED, fromStaffId: $fromStaffId, metadata: ['threshold_minutes' => 15]);
                    $this->recordTicketEvent($ticket, SupportTicketEventType::REASSIGNED, fromStaffId: $fromStaffId, metadata: ['reason' => 'sla_breached']);
                    $ticket->load(['customer.profile', 'assignedStaff', 'category', 'latestBooking.service', 'latestMessage.customer', 'latestMessage.staff']);
                    $this->notifySlaAdmins($ticket, warning: false);
                    $this->publishSupportEvent(NodeServerConstant::SUPPORT_SLA_BREACHED, ['ticket' => $this->serializeTicket($ticket), 'broadcast_staff_ids' => $this->onlineSupportStaffIds()]);
                    $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_REASSIGNED, ['ticket' => $this->serializeTicket($ticket), 'broadcast_staff_ids' => $this->onlineSupportStaffIds()]);
                    $breachedIds[] = (string) $ticket->id;
                    return ServiceReturn::success();
                }, useTransaction: true);
            });

        return ['warning_ids' => $warningIds, 'breached_ids' => $breachedIds];
    }

    protected function notifySlaAdmins(SupportTicket $ticket, bool $warning): void
    {
        $admins = $this->adminUserRepository->queryAdminUser()
            ->where('role', AdminRole::SUPER_ADMIN->value)
            ->where('is_active', true)
            ->get();
        if ($admins->isEmpty()) return;

        $notification = FilamentNotification::make()
            ->title($warning ? 'Cảnh báo SLA hỗ trợ' : 'Ticket đã vi phạm SLA')
            ->body("Ticket #{$ticket->id} - " . ($ticket->customer?->name ?? 'Khách hàng'))
            ->persistent()
            ->{$warning ? 'warning' : 'danger'}();
        $notification->sendToDatabase($admins, isEventDispatched: true);
    }

    public function heartbeatAdmin(int $adminId): ServiceReturn
    {
        try {
            $admin = $this->adminUserRepository->find($adminId);
            if (!$admin || !$admin->is_active) {
                return ServiceReturn::error(__('common_error.unauthorized'));
            }

            $admin->timestamps = false;
            $admin->last_seen_at = now();
            $admin->save();

            Caching::setCache(CacheKey::CACHE_USER_HEARTBEAT, true, "admin:{$adminId}", 5);
            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@heartbeatAdmin', $exception);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function issueAdminSocketToken(AdminUser $admin): string
    {
        $expiresAt = now()->addMinutes(30)->timestamp;
        $nonce = Str::random(12);
        $secret = config('services.node_server.admin_socket_secret', config('app.key'));
        $payload = "admin.{$admin->id}.{$expiresAt}.{$nonce}";
        $signature = hash_hmac('sha256', $payload, (string) $secret);
        return "{$payload}.{$signature}";
    }

    protected function selectOnlineStaff(): ?AdminUser
    {
        $activeStatuses = [
            SupportTicketStatus::PENDING->dbValue(),
            SupportTicketStatus::ASSIGNED->dbValue(),
            SupportTicketStatus::IN_PROGRESS->dbValue(),
        ];

        $onlineStaff = $this->adminUserRepository->queryAdminUser()
            ->where('role', AdminRole::CUSTOMER_SUPPORT->value)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->lockForUpdate()
            ->get()
            ->filter(function (AdminUser $admin) {
                if ($admin->last_seen_at && $admin->last_seen_at->diffInMinutes(now()) <= 3) {
                    return true;
                }
                return Caching::hasCache(CacheKey::CACHE_USER_HEARTBEAT, "admin:{$admin->id}");
            });

        if ($onlineStaff->isEmpty()) {
            return null;
        }

        // Least-loaded first, then the least recently active account for fairness.
        $openCounts = $this->supportTicketRepository->query()
            ->selectRaw('assigned_staff_id, COUNT(*) as total')
            ->whereIn('status', $activeStatuses)
            ->whereIn('assigned_staff_id', $onlineStaff->pluck('id')->all())
            ->groupBy('assigned_staff_id')
            ->pluck('total', 'assigned_staff_id');

        return $onlineStaff
            ->sortBy(fn (AdminUser $admin) => [
                (int) ($openCounts[(string) $admin->id] ?? 0),
                $admin->last_seen_at?->timestamp ?? 0,
            ])
            ->first();
    }

    public function onlineSupportStaffIds(): array
    {
        return $this->onlineSupportStaff()->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
    }

    protected function onlineSupportStaff()
    {
        return $this->adminUserRepository->queryAdminUser()
            ->where('role', AdminRole::CUSTOMER_SUPPORT->value)
            ->where('is_active', true)
            ->get()
            ->filter(fn (AdminUser $admin) =>
                ($admin->last_seen_at && $admin->last_seen_at->diffInMinutes(now()) <= 3)
                || Caching::hasCache(CacheKey::CACHE_USER_HEARTBEAT, "admin:{$admin->id}")
            );
    }

    protected function onlineSupportStaffCount(): int
    {
        return $this->onlineSupportStaff()->count();
    }

    public function recordTicketEvent(SupportTicket $ticket, SupportTicketEventType $type, ?int $actorAdminId = null, ?int $fromStaffId = null, ?int $toStaffId = null, ?array $metadata = null): SupportTicketEvent
    {
        return SupportTicketEvent::create([
            'support_ticket_id' => $ticket->id,
            'actor_admin_id' => $actorAdminId,
            'event_type' => $type->value,
            'from_staff_id' => $fromStaffId,
            'to_staff_id' => $toStaffId,
            'metadata' => $metadata,
        ]);
    }

    protected function makeRoomId(string|int $ticketId): string
    {
        return "support-ticket:{$ticketId}";
    }

    protected function publishSupportEvent(string $type, array $payload): void
    {
        RedisFacade::connection()->publish(
            config('services.node_server.channel_support'),
            json_encode([
                'type' => $type,
                'payload' => $payload,
            ])
        );
    }

    protected function serializeTicket(SupportTicket $ticket): array
    {
        return [
            'id' => (string) $ticket->id,
            'room_id' => $ticket->room_id,
            'status' => $ticket->statusEnum()->value,
            'status_label' => $ticket->statusEnum()->label(),
            'customer' => [
                'id' => (string) $ticket->customer_id,
                'name' => $ticket->customer?->name,
                'avatar' => $ticket->customer?->profile?->avatar_url,
            ],
            'assigned_staff' => $ticket->assignedStaff ? [
                'id' => (string) $ticket->assignedStaff->id,
                'name' => $ticket->assignedStaff->name,
            ] : null,
            'category' => [
                'id' => (string) $ticket->category?->id,
                'name' => $ticket->category?->getTranslations('name'),
            ],
            'latest_booking' => $ticket->latestBooking ? [
                'id' => (string) $ticket->latestBooking->id,
                'booking_time' => $ticket->latestBooking->booking_time?->toISOString(),
                'status' => $ticket->latestBooking->status ?? null,
                'service_name' => $ticket->latestBooking->service?->name ?? null,
            ] : null,
            'last_message_at' => $ticket->last_message_at?->toISOString(),
            'assigned_at' => $ticket->assigned_at?->toISOString(),
            'first_response_at' => $ticket->first_response_at?->toISOString(),
            'closed_at' => $ticket->closed_at?->toISOString(),
            'sla_warning_at' => $ticket->sla_warning_at?->toISOString(),
            'sla_breached_at' => $ticket->sla_breached_at?->toISOString(),
            'is_sla_breached' => (bool) $ticket->sla_breached_at,
            'latest_message' => $ticket->latestMessage ? $this->serializeMessage($ticket->latestMessage) : null,
        ];
    }

    protected function serializeMessage(SupportMessage $message): array
    {
        return [
            'id' => (string) $message->id,
            'support_ticket_id' => (string) $message->support_ticket_id,
            'content' => $message->content,
            'sender_type' => $message->senderTypeEnum()->value,
            'sender_user_id' => $message->sender_user_id ? (string) $message->sender_user_id : null,
            'sender_admin_id' => $message->sender_admin_id ? (string) $message->sender_admin_id : null,
            'temp_id' => $message->temp_id,
            'seen_at' => $message->seen_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
            'sender_name' => $message->customer?->name ?? $message->staff?->name,
            'sender_avatar' => $message->customer?->profile?->avatar_url,
        ];
    }
}
