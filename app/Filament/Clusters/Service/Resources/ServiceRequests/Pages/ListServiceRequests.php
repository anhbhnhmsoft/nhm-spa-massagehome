<?php

namespace App\Filament\Clusters\Service\Resources\ServiceRequests\Pages;

use App\Filament\Clusters\Service\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
