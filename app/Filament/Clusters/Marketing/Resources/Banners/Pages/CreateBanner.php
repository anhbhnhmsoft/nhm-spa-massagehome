<?php

namespace App\Filament\Clusters\Marketing\Resources\Banners\Pages;

use App\Filament\Clusters\Marketing\Resources\Banners\BannerResource;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\CreateRecord;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

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
        return __('common.breadcrumb.create');
    }
}
