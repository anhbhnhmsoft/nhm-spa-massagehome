<?php

namespace App\Filament\Clusters\HumanResource\Resources\Staffs\Pages;

use App\Enums\Admin\AdminRole;
use App\Filament\Clusters\HumanResource\Resources\Staffs\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = AdminRole::CUSTOMER_SUPPORT->value;
        return $data;
    }
}
