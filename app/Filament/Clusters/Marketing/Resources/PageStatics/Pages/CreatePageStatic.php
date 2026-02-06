<?php

namespace App\Filament\Clusters\Marketing\Resources\PageStatics\Pages;

use App\Filament\Clusters\Marketing\Resources\PageStatics\PageStaticResource;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\CreateRecord;

class CreatePageStatic extends CreateRecord
{
    protected static string $resource = PageStaticResource::class;
    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
