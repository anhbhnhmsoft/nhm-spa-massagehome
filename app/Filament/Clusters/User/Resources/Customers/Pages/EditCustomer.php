<?php

namespace App\Filament\Clusters\User\Resources\Customers\Pages;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Clusters\User\Resources\Customers\Widgets\TransactionCustomerTable;
use App\Filament\Clusters\User\Resources\Customers\Widgets\WalletCustomer;
use App\Filament\Components\CommonActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make()
                ->label(__('common.action.delete')),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WalletCustomer::class,
            TransactionCustomerTable::class,
        ];
    }


    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('common.action.save')),
            $this->getCancelFormAction()
                ->label(__('common.action.cancel')),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
