<?php

namespace App\Filament\Clusters\Marketing\Resources\PageStatics\Pages;

use App\Filament\Clusters\Marketing\Resources\PageStatics\PageStaticResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageStatics extends ListRecords
{
    protected static string $resource = PageStaticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('common.action.create')),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }
}
