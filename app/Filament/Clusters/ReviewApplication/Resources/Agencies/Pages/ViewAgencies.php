<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages;


use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAgencies extends ViewRecord
{
    protected static string $resource = AgencyResource::class;

    public function getTitle(): string
    {
        return __('admin.agency.label');
    }
}
