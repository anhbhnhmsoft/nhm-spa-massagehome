<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets\Schemas;

use App\Enums\Admin\AdminRole;
use App\Enums\SupportTicketStatus;
use App\Models\AdminUser;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.support_ticket.section.info'))
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Placeholder::make('customer_name')
                            ->label(__('admin.support_ticket.fields.customer'))
                            ->content(fn ($record) => $record?->customer?->name ?? '-'),
                        Placeholder::make('customer_phone')
                            ->label(__('admin.support_ticket.fields.customer_phone'))
                            ->content(fn ($record) => $record?->customer?->phone ?? '-'),
                        Placeholder::make('category_name')
                            ->label(__('admin.support_ticket.fields.category'))
                            ->content(fn ($record) => $record?->category?->getTranslation('name', app()->getLocale()) ?? '-'),
                        Placeholder::make('room_id')
                            ->label(__('admin.support_ticket.fields.room_id'))
                            ->content(fn ($record) => $record?->room_id ?? '-'),
                        Placeholder::make('latest_booking')
                            ->label(__('admin.support_ticket.fields.latest_booking'))
                            ->content(fn ($record) => $record?->latestBooking?->id ?? '-'),
                        Placeholder::make('last_message_at')
                            ->label(__('admin.support_ticket.fields.last_message_at'))
                            ->content(fn ($record) => $record?->last_message_at?->toDateTimeString() ?? '-'),
                    ]),
                Section::make(__('admin.support_ticket.section.manage'))
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Select::make('assigned_staff_id')
                            ->label(__('admin.support_ticket.fields.assigned_staff'))
                            ->options(fn () => AdminUser::query()
                                ->where('role', AdminRole::EMPLOYEE->value)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->placeholder(__('admin.common.select_placeholder'))
                            ->nullable(),
                        Select::make('status')
                            ->label(__('admin.support_ticket.fields.status'))
                            ->options(SupportTicketStatus::toOptions())
                            ->required(),
                        Placeholder::make('latest_message')
                            ->label(__('admin.support_ticket.fields.latest_message'))
                            ->columnSpanFull()
                            ->content(fn ($record) => $record?->latestMessage?->content ?? '-'),
                    ]),
            ]);
    }
}
