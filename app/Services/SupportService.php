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
use App\Enums\SupportTicketStatus;
use App\Enums\Admin\AdminRole;
use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Repositories\AdminUserRepository;
use App\Repositories\BookingRepository;
use App\Repositories\SupportCategoryRepository;
use App\Repositories\SupportMessageRepository;
use App\Repositories\SupportTicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis as RedisFacade;
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
            }
            $ticket->save();

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
            ]);

            return ServiceReturn::success(data: [
                'ticket' => $ticket,
            ]);
        }, useTransaction: true);
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
                $query->where('assigned_staff_id', $staffId);
            } elseif ($scope === 'pending') {
                $query->whereNull('assigned_staff_id')->where('status', SupportTicketStatus::PENDING->dbValue());
            } elseif ($scope === 'open') {
                $query->whereIn('status', [
                    SupportTicketStatus::PENDING->dbValue(),
                    SupportTicketStatus::ASSIGNED->dbValue(),
                    SupportTicketStatus::IN_PROGRESS->dbValue(),
                ])->where(function ($q) use ($staffId) {
                    $q->whereNull('assigned_staff_id')
                      ->orWhere('assigned_staff_id', $staffId);
                });
            }
            $paginator = $query->orderByDesc('last_message_at')->paginate(perPage: $perPage, page: $page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            LogHelper::error('Lỗi SupportService@listStaffTickets', $exception);
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
            $ticket = $this->supportTicketRepository->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $senderType = $senderType ?? ($user instanceof AdminUser ? SupportMessageSenderType::STAFF : SupportMessageSenderType::CUSTOMER);
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
            } elseif ($ticket->status === SupportTicketStatus::PENDING->dbValue()) {
                $ticket->status = SupportTicketStatus::ASSIGNED;
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
            $ticket = $this->supportTicketRepository->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ($ticket->assigned_staff_id && (string) $ticket->assigned_staff_id !== (string) $staffId) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $staff = $this->adminUserRepository->find($staffId);
            if (!$staff || !$staff->is_active) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $ticket->assigned_staff_id = $staffId;
            $ticket->status = SupportTicketStatus::ASSIGNED;
            $ticket->save();

            $message = $this->supportMessageRepository->create([
                'support_ticket_id' => $ticket->id,
                'sender_type' => \App\Enums\SupportMessageSenderType::STAFF,
                'sender_admin_id' => $staff->id,
                'content' => "Xin chào, tôi là {$staff->name}, nhân viên phụ trách sẽ hỗ trợ bạn.",
            ]);
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
        return $this->execute(function () use ($ticketId, $staffId) {
            $ticket = $this->supportTicketRepository->find($ticketId);
            if (!$ticket) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            if ((string) $ticket->assigned_staff_id !== (string) $staffId) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $ticket->status = SupportTicketStatus::CLOSED;
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
            $this->publishSupportEvent(NodeServerConstant::SUPPORT_TICKET_CLOSED, [
                'ticket' => $this->serializeTicket($ticket),
            ]);

            return ServiceReturn::success(data: $ticket);
        }, useTransaction: true);
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
        return $this->adminUserRepository->queryAdminUser()
            ->where('role', AdminRole::EMPLOYEE->value)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->get()
            ->first(function (AdminUser $admin) {
                if ($admin->last_seen_at && $admin->last_seen_at->diffInMinutes(now()) <= 3) {
                    return true;
                }
                return Caching::hasCache(CacheKey::CACHE_USER_HEARTBEAT, "admin:{$admin->id}");
            });
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
