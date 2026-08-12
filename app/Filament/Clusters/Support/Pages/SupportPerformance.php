<?php

namespace App\Filament\Clusters\Support\Pages;

use App\Enums\Admin\AdminGate;
use App\Enums\Admin\AdminRole;
use App\Enums\SupportMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Filament\Clusters\Support\SupportCluster;
use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class SupportPerformance extends Page
{
    protected static ?string $cluster = SupportCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.pages.support-performance';

    public string $date;

    public ?string $staffId = null;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_SUPPORT_MANAGEMENT);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.support_monitor.performance');
    }

    public function getTitle(): string
    {
        return __('admin.support_monitor.performance');
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getStaffOptionsProperty(): array
    {
        return AdminUser::query()
            ->where('role', AdminRole::CUSTOMER_SUPPORT->value)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
            ->all();
    }

    public function getRowsProperty(): array
    {
        $day = Carbon::parse($this->date ?: now()->toDateString());
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();
        $staffQuery = AdminUser::query()
            ->where('role', AdminRole::CUSTOMER_SUPPORT->value)
            ->orderBy('name');
        if ($this->staffId) {
            $staffQuery->whereKey($this->staffId);
        }

        return $staffQuery->get()->map(function (AdminUser $staff) use ($from, $to) {
            $tickets = SupportTicket::query()
                ->with('messages')
                ->where('assigned_staff_id', $staff->id)
                ->where(function ($query) use ($from, $to) {
                    $query->whereBetween('created_at', [$from, $to])
                        ->orWhereBetween('updated_at', [$from, $to]);
                })
                ->get();
            $messages = SupportMessage::query()
                ->where('sender_admin_id', $staff->id)
                ->whereBetween('created_at', [$from, $to])
                ->count();
            $closed = $tickets->where('status', SupportTicketStatus::CLOSED->dbValue())->count();
            $active = $tickets->whereIn('status', [
                SupportTicketStatus::ASSIGNED->dbValue(),
                SupportTicketStatus::IN_PROGRESS->dbValue(),
            ])->count();

            $responseSeconds = [];
            $processingSeconds = [];
            foreach ($tickets as $ticket) {
                $firstCustomer = $ticket->messages
                    ->where('sender_type', SupportMessageSenderType::CUSTOMER->dbValue())
                    ->sortBy('created_at')
                    ->first();
                $firstStaff = $ticket->messages
                    ->where('sender_admin_id', $staff->id)
                    ->sortBy('created_at')
                    ->first();
                if ($firstCustomer && $firstStaff && $firstStaff->created_at->greaterThanOrEqualTo($firstCustomer->created_at)) {
                    $responseSeconds[] = $firstCustomer->created_at->diffInSeconds($firstStaff->created_at);
                }
                if ($ticket->assigned_at && $ticket->closed_at) {
                    $processingSeconds[] = $ticket->assigned_at->diffInSeconds($ticket->closed_at);
                }
            }

            $claimed = SupportTicketEvent::query()->where('event_type', 'claimed')->where('to_staff_id', $staff->id)->whereBetween('created_at', [$from, $to])->count();
            $reassigned = SupportTicketEvent::query()->where('event_type', 'reassigned')->where('from_staff_id', $staff->id)->whereBetween('created_at', [$from, $to])->count();
            $slaWarning = SupportTicket::query()->where('assigned_staff_id', $staff->id)->whereNotNull('sla_warning_at')->whereBetween('sla_warning_at', [$from, $to])->count();
            $slaBreached = SupportTicketEvent::query()->where('event_type', 'sla_breached')->where('from_staff_id', $staff->id)->whereBetween('created_at', [$from, $to])->count();
            $closedWithSla = $tickets->filter(fn ($ticket) => $ticket->status === SupportTicketStatus::CLOSED->dbValue() && $ticket->closed_at && $ticket->closed_at->diffInMinutes($ticket->created_at) <= 15)->count();

            return [
                'id' => (string) $staff->id,
                'name' => $staff->name,
                'username' => $staff->username,
                'online' => $staff->last_seen_at?->greaterThan(now()->subMinutes(3)) ?? false,
                'received' => $tickets->whereBetween('created_at', [$from, $to])->count(),
                'claimed' => $claimed,
                'active' => $active,
                'closed' => $closed,
                'messages' => $messages,
                'unread' => SupportMessage::query()
                    ->where('sender_type', SupportMessageSenderType::CUSTOMER->dbValue())
                    ->whereNull('seen_at')
                    ->whereHas('ticket', fn ($query) => $query->where('assigned_staff_id', $staff->id))
                    ->count(),
                'avg_response_seconds' => count($responseSeconds) ? (int) round(array_sum($responseSeconds) / count($responseSeconds)) : null,
                'avg_processing_seconds' => count($processingSeconds) ? (int) round(array_sum($processingSeconds) / count($processingSeconds)) : null,
                'reassigned' => $reassigned,
                'sla_warning' => $slaWarning,
                'sla_breached' => $slaBreached,
                'sla_rate' => $closed > 0 ? (int) round($closedWithSla / $closed * 100) : null,
            ];
        })->all();
    }
}
