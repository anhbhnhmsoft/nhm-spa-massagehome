<?php

namespace App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages;

use App\Filament\Clusters\Agency\Resources\AgencyApplies\AgencyApplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgencyApplies extends ListRecords
{
    protected static string $resource = AgencyApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
