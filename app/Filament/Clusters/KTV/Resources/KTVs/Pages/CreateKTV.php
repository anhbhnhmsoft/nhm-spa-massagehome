<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Core\Helper;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKTV extends CreateRecord
{
    protected static string $resource = KTVResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set role to KTV
        $data['role'] = UserRole::KTV->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;
        return $data;
    }
}
