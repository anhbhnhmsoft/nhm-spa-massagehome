<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets\Pages;

use App\Filament\Clusters\Support\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('common.action.save')),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->icon('heroicon-m-check');
    }
}
