<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKTVApplies extends ListRecords
{
    protected static string $resource = KTVApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
