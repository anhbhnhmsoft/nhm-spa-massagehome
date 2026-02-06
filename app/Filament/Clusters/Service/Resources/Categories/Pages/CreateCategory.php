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
    
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(__('common.action.create')),
            $this->getCreateAnotherFormAction()
                ->label(__('common.action.create_another')),
            $this->getCancelFormAction()
                ->label(__('common.action.cancel')),
        ];
    }   
    
    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
