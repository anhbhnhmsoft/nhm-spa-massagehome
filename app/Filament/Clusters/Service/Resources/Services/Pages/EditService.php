<?php

namespace App\Filament\Clusters\Service\Resources\Services\Pages;

use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
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
            $this->getSaveFormAction()
                ->label(__('common.action.save')),
            $this->getCancelFormAction()
                ->label(__('common.action.cancel')),
        ];
    }
    
    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
