<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets;

use App\Enums\Admin\AdminGate;
use App\Enums\SupportTicketStatus;
use App\Filament\Clusters\Support\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Clusters\Support\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Clusters\Support\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Clusters\Support\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $cluster = \App\Filament\Clusters\Support\SupportCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_EMPLOYEE);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return Gate::allows(AdminGate::ALLOW_EMPLOYEE);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.support_ticket.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.support_ticket.label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', SupportTicketStatus::PENDING->dbValue())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'customer.profile',
            'assignedStaff',
            'category',
            'latestBooking.service',
            'latestMessage.customer.profile',
            'latestMessage.staff',
        ]);
    }
}
