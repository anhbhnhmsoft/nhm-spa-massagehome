<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Pages;

use App\Filament\Clusters\Service\Resources\Categories\CategoryResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }
}
