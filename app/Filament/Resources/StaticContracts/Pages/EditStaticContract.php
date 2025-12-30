<?php

namespace App\Filament\Resources\StaticContracts\Pages;

use App\Enums\ContractFileType;
use App\Filament\Resources\StaticContracts\StaticContractResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditStaticContract extends EditRecord
{
    protected static string $resource = StaticContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = ContractFileType::getSlug($data['type']);
        return $data;
    }
}
