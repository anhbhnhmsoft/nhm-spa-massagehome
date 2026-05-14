<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories\Pages;

use App\Filament\Clusters\Support\Resources\SupportCategories\SupportCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportCategories extends ListRecords
{
    protected static string $resource = SupportCategoryResource::class;

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

