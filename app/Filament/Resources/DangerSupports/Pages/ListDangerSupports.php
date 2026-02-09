<?php

namespace App\Filament\Resources\DangerSupports\Pages;

use App\Filament\Resources\DangerSupports\DangerSupportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDangerSupports extends ListRecords
{
    protected static string $resource = DangerSupportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
