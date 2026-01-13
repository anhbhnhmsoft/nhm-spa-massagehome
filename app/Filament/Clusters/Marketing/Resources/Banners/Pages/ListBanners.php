<?php

namespace App\Filament\Clusters\Marketing\Resources\Banners\Pages;

use App\Filament\Clusters\Marketing\Resources\Banners\BannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.common.action.create')),
        ];
    }
}
