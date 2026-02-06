<?php

namespace App\Filament\Clusters\Service\Resources\Services\Pages;

use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
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
