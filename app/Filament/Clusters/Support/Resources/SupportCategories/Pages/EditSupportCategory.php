<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories\Pages;

use App\Filament\Clusters\Support\Resources\SupportCategories\SupportCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditSupportCategory extends EditRecord
{
    protected static string $resource = SupportCategoryResource::class;

    public function getBreadcrumb(): string
    {
        return __('common.action.edit');
    }
}

