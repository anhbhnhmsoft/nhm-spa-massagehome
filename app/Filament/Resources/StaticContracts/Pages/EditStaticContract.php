<?php

namespace App\Filament\Resources\StaticContracts\Pages;

use App\Enums\ContractFileType;
use App\Filament\Components\CommonActions;
use App\Filament\Resources\StaticContracts\StaticContractResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaticContract extends EditRecord
{
    protected static string $resource = StaticContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('common.breadcrumb.edit');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = ContractFileType::getSlug($data['type']);
        return $data;
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
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
}
