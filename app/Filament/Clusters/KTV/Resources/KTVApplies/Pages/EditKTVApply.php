<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTVApply extends EditRecord
{
    protected static string $resource = KTVApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}


