<?php

namespace App\Filament\Clusters\HumanResource\Resources\Staffs\Pages;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\HumanResource\Resources\Staffs\StaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Gate::allows(AdminGate::ALLOW_ADMIN)),
        ];
    }
}
