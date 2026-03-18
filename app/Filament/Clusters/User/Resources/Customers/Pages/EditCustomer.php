<?php

namespace App\Filament\Clusters\User\Resources\Customers\Pages;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Clusters\User\Resources\Customers\Widgets\TransactionCustomerTable;
use App\Filament\Components\CommonActions;
use App\Filament\Widgets\WalletStats;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            CommonActions::deleteAction(),

        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WalletStats::class,
            TransactionCustomerTable::class,
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
        $isLocked = !Gate::allows(AdminGate::ALLOW_ADMIN);
        return parent::getSaveFormAction()
            ->color($isLocked ? 'gray' : 'primary')
            ->icon($isLocked ? 'heroicon-m-lock-closed' : 'heroicon-m-check')
            ->disabled($isLocked);
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
