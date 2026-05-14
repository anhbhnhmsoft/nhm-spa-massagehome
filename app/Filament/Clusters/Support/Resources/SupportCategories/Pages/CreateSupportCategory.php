<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories\Pages;

use App\Filament\Clusters\Support\Resources\SupportCategories\SupportCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportCategory extends CreateRecord
{
    protected static string $resource = SupportCategoryResource::class;

    public function getBreadcrumb(): string
    {
        return __('common.action.create');
    }
}

