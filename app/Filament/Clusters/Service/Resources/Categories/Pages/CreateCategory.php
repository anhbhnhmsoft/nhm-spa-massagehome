<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Pages;

use App\Filament\Clusters\Service\Resources\Categories\CategoryResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource())
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
