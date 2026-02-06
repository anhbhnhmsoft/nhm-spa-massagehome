<?php

namespace App\Filament\Clusters\User\Resources\Customers\Pages;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonActions;
use App\Models\Wallet;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource())
        ];
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (!$record->wallet) {
            Wallet::create([
                'user_id' => $record->id,
                'balance' => 0,
                'is_active' => true,
            ]);
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(__('common.action.create')),
            $this->getCreateAnotherFormAction()
                ->label(__('common.action.create_another')),
            $this->getCancelFormAction()
                ->label(__('common.action.cancel')),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
