<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTV extends EditRecord
{
    protected static string $resource = KTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
