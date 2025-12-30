<?php

namespace App\Filament\Resources\StaticContracts\Pages;

use App\Filament\Resources\StaticContracts\StaticContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaticContracts extends ListRecords
{
    protected static string $resource = StaticContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
