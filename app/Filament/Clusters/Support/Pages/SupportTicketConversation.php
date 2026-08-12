<?php

namespace App\Filament\Clusters\Support\Pages;

use App\Enums\Admin\AdminGate;
use App\Enums\SupportMessageSenderType;
use App\Filament\Clusters\Support\SupportCluster;
use App\Models\SupportTicket;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class SupportTicketConversation extends Page
{
    protected static ?string $cluster = SupportCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'support-tickets/{ticket}/conversation';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.support-ticket-conversation';

    public string $ticketId;

    public int $messageLimit = 100;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_SUPPORT_MANAGEMENT);
    }

    public function mount(string $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function getTicketProperty(): ?SupportTicket
    {
        return SupportTicket::query()
            ->with([
                'customer.profile',
                'assignedStaff',
                'category',
                'latestBooking.service',
            ])
            ->find($this->ticketId);
    }

    public function getMessagesProperty()
    {
        return $this->ticket?->messages()
            ->with(['customer.profile', 'staff'])
            ->orderByDesc('created_at')
            ->take($this->messageLimit)
            ->get()
            ->reverse()
            ->values() ?? collect();
    }

    public function getHasMoreMessagesProperty(): bool
    {
        return (int) ($this->ticket?->messages()->count() ?? 0) > $this->messageLimit;
    }

    public function loadOlderMessages(): void
    {
        $this->messageLimit += 100;
    }

    public function getEventsProperty()
    {
        return $this->ticket?->events()
            ->with(['actor', 'fromStaff', 'toStaff'])
            ->latest()
            ->limit(100)
            ->get() ?? collect();
    }

    public function isStaffMessage($message): bool
    {
        return $message->senderTypeEnum() === SupportMessageSenderType::STAFF;
    }

    public function getTitle(): string
    {
        return __('admin.support_monitor.conversation_detail');
    }
}
