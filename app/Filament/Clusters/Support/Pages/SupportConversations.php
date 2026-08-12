<?php

namespace App\Filament\Clusters\Support\Pages;

use App\Enums\Admin\AdminGate;
use App\Enums\SupportMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Filament\Clusters\Support\SupportCluster;
use App\Models\SupportTicket;
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

    public function getTicketsProperty()
    {
        $query = SupportTicket::query()
            ->with(['customer.profile', 'assignedStaff', 'category', 'latestMessage.customer', 'latestMessage.staff'])
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

    public function isStaffMessage($message): bool
    {
        return $message->senderTypeEnum() === SupportMessageSenderType::STAFF;
    }
}
