<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Components\CommonActions;
use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }
}
