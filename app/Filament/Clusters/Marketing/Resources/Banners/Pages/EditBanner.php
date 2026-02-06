<?php

namespace App\Filament\Clusters\Marketing\Resources\Banners\Pages;

use App\Filament\Clusters\Marketing\Resources\Banners\BannerResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
