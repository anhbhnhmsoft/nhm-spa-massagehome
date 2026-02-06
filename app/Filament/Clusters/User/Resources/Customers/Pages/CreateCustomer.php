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


    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
