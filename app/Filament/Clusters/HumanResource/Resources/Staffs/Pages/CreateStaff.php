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
        $data['role'] = AdminRole::EMPLOYEE->value; // Mặc định khi tạo mới sẽ là nhân viên
        return $data;
    }
}
