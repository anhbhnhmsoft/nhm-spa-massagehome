<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Pages;

use App\Filament\Clusters\Agency\Resources\Agencies\AgencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.common.action.create')),
        ];
    }
}
