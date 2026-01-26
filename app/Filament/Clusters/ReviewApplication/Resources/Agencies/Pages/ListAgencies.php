<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages;

use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use Filament\Resources\Pages\ListRecords;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
