<?php

namespace App\Filament\Clusters\User\Resources\Customers\Pages;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Models\Wallet;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

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
}
