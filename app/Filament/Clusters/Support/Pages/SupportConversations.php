<?php

namespace App\Filament\Clusters\Support\Pages;

use App\Enums\Admin\AdminGate;
use App\Enums\SupportMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Filament\Clusters\Support\SupportCluster;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class SupportConversations extends Page
{
    protected static ?string $cluster = SupportCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.support-conversations';

    public ?string $ticketId = null;
    public string $statusFilter = 'active';
    public ?string $staffFilter = null;
    public ?string $categoryFilter = null;
    public string $slaFilter = 'all';

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_SUPPORT_MANAGEMENT);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.support_monitor.conversations');
    }

    public function getTitle(): string
    {
        return __('admin.support_monitor.conversations');
    }

    public function mount(): void
    {
        $this->ticketId = $this->tickets->first()?->id;
    }

    public function updatedStatusFilter(): void { $this->selectFirstTicket(); }
    public function updatedStaffFilter(): void { $this->selectFirstTicket(); }
    public function updatedCategoryFilter(): void { $this->selectFirstTicket(); }
    public function updatedSlaFilter(): void { $this->selectFirstTicket(); }

    protected function selectFirstTicket(): void
    {
        $this->ticketId = $this->tickets->first()?->id;
    }

    public function getQueueSummaryProperty(): array
    {
        $active = [SupportTicketStatus::PENDING->dbValue(), SupportTicketStatus::ASSIGNED->dbValue(), SupportTicketStatus::IN_PROGRESS->dbValue()];
        $query = SupportTicket::query()->whereIn('status', $active);

        return [
            'pending' => (clone $query)->whereNull('assigned_staff_id')->count(),
            'assigned' => (clone $query)->whereNotNull('assigned_staff_id')->count(),
            'unread' => SupportTicket::query()->whereIn('status', $active)->whereHas('messages', fn ($q) => $q->where('sender_type', SupportMessageSenderType::CUSTOMER->dbValue())->whereNull('seen_at'))->count(),
            'warning' => (clone $query)->whereNotNull('sla_warning_at')->whereNull('sla_breached_at')->count(),
            'breached' => (clone $query)->whereNotNull('sla_breached_at')->count(),
            'online' => \App\Models\AdminUser::query()->where('role', \App\Enums\Admin\AdminRole::CUSTOMER_SUPPORT->value)->where('is_active', true)->where('last_seen_at', '>=', now()->subMinutes(3))->count(),
        ];
    }

    public function getTicketsProperty()
    {
        $query = SupportTicket::query()
            ->with(['customer.profile', 'assignedStaff', 'category', 'latestMessage.customer', 'latestMessage.staff'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', SupportMessageSenderType::CUSTOMER->dbValue())->whereNull('seen_at')])
            ->orderByDesc(DB::raw("CASE WHEN sla_breached_at IS NOT NULL THEN 2 WHEN sla_warning_at IS NOT NULL THEN 1 ELSE 0 END"))
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');
        if ($this->statusFilter === 'active') {
            $query->whereIn('status', [SupportTicketStatus::PENDING->dbValue(), SupportTicketStatus::ASSIGNED->dbValue(), SupportTicketStatus::IN_PROGRESS->dbValue()]);
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', SupportTicketStatus::tryFrom($this->statusFilter)?->dbValue() ?? -1);
        }
        if ($this->staffFilter) $query->where('assigned_staff_id', $this->staffFilter);
        if ($this->categoryFilter) $query->where('category_id', $this->categoryFilter);
        if ($this->slaFilter === 'warning') $query->whereNotNull('sla_warning_at')->whereNull('sla_breached_at');
        if ($this->slaFilter === 'breached') $query->whereNotNull('sla_breached_at');
        return $query->limit(100)->get();
    }

    public function getSelectedTicketProperty(): ?SupportTicket
    {
        if (!$this->ticketId) {
            return null;
        }

        return $this->tickets->firstWhere('id', $this->ticketId);
    }

    public function getMessagesProperty()
    {
        return $this->selectedTicket?->messages()
            ->with(['customer.profile', 'staff'])
            ->orderBy('created_at')
            ->limit(200)
            ->get() ?? collect();
    }

    public function getEventsProperty()
    {
        return $this->selectedTicket?->events()->with(['actor', 'fromStaff', 'toStaff'])->latest()->limit(30)->get() ?? collect();
    }

    public function isStaffMessage($message): bool
    {
        return $message->senderTypeEnum() === SupportMessageSenderType::STAFF;
    }
}
