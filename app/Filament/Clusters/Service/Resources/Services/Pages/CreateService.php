<?php

namespace App\Filament\Clusters\Service\Resources\Services\Pages;

use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
}
