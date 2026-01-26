<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages;

use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKTVs extends ListRecords
{
    protected static string $resource = KTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            CreateAction::make()
//                ->label(__('admin.common.action.create')),
        ];
    }
}
