<?php

namespace App\Filament\Resources\StaticContracts\Pages;

use App\Enums\ContractFileType;
use App\Filament\Components\CommonActions;
use App\Filament\Resources\StaticContracts\StaticContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaticContract extends CreateRecord
{
    protected static string $resource = StaticContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = ContractFileType::getSlug($data['type']);
        return $data;
    }
}
