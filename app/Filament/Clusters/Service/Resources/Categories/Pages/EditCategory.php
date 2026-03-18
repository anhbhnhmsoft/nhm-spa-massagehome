<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Pages;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\Service\Resources\Categories\CategoryResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

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

    protected function getSaveFormAction(): Action
    {
        $isLocked = !Gate::allows(AdminGate::ALLOW_ADMIN);
        return parent::getSaveFormAction()
            // 3. Đổi màu sang xám nếu bị khóa (tạo cảm giác disabled)
            ->color($isLocked ? 'gray' : 'primary')
            ->icon($isLocked ? 'heroicon-m-lock-closed' : 'heroicon-m-check')
            ->disabled($isLocked);
    }
    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
