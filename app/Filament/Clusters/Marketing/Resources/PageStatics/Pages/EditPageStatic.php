<?php

namespace App\Filament\Clusters\Marketing\Resources\PageStatics\Pages;

use App\Filament\Clusters\Marketing\Resources\PageStatics\PageStaticResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPageStatic extends EditRecord
{
    protected static string $resource = PageStaticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }
}
