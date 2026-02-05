<?php

namespace App\Filament\Clusters\Service\Resources\Services\Pages;

use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }


    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }
}
