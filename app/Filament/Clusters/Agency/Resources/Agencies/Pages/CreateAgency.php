<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Pages;

use App\Core\Helper;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\Agency\Resources\Agencies\AgencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgency extends CreateRecord
{
    protected static string $resource = AgencyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::AGENCY->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;
        $data['status'] = ReviewApplicationStatus::APPROVED->value;
        return $data;
    }
}
